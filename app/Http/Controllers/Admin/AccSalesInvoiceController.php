<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccContact;
use App\Models\AccProduct;
use App\Models\AccSalesInvoice;
use App\Models\Website;
use App\Services\Accounting\JournalPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccSalesInvoiceController extends Controller
{
    public function __construct(private JournalPoster $poster) {}

    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    private function nextNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';
        $last = AccSalesInvoice::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $query = AccSalesInvoice::with('contact')->orderByDesc('date')->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('q')) {
            $query->where('number', 'like', "%{$search}%");
        }

        return view('admin.accounting.sales-invoices', [
            'title' => 'Faktur Penjualan',
            'invoices' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function create()
    {
        return view('admin.accounting.sales-invoice-form', [
            'title' => 'Faktur Penjualan Baru',
            'invoice' => null,
            'contacts' => AccContact::where('is_active', true)->orderBy('name')->get(),
            'products' => AccProduct::where('is_active', true)->orderBy('name')->get(),
            'accounts' => AccAccount::where('type', 'revenue')->orderBy('code')->get(),
            'suggestedNumber' => $this->nextNumber(),
        ] + $this->websiteData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateInvoice($request);
        $totals = $this->computeTotals($request);

        DB::transaction(function () use ($validated, $request, $totals) {
            $invoice = AccSalesInvoice::create([
                'number' => $validated['number'] ?: $this->nextNumber(),
                'contact_id' => $validated['contact_id'],
                'date' => $validated['date'],
                'due_date' => $validated['due_date'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'paid' => 0,
                'status' => 'unpaid',
                'created_by' => auth()->id(),
            ]);

            $this->saveItems($invoice, $request);
            $this->postSalesJournal($invoice->fresh('items'));
        });

        return redirect(url('admin/accounting/sales'))->with('success', ['Faktur penjualan berhasil dibuat']);
    }

    public function show($id)
    {
        $invoice = AccSalesInvoice::with(['items', 'contact'])->findOrFail($id);

        return view('admin.accounting.sales-invoice-detail', [
            'title' => 'Faktur '.$invoice->number,
            'invoice' => $invoice,
            'cashAccounts' => AccAccount::where('is_cash', true)->orderBy('code')->get(),
        ] + $this->websiteData());
    }

    public function edit($id)
    {
        $invoice = AccSalesInvoice::with('items')->findOrFail($id);

        if ($invoice->paid > 0) {
            return redirect(url('admin/accounting/sales'))->with('auth_errors', ['Faktur yang sudah dibayar tidak dapat diedit']);
        }

        return view('admin.accounting.sales-invoice-form', [
            'title' => 'Edit Faktur '.$invoice->number,
            'invoice' => $invoice,
            'contacts' => AccContact::where('is_active', true)->orderBy('name')->get(),
            'products' => AccProduct::where('is_active', true)->orderBy('name')->get(),
            'accounts' => AccAccount::where('type', 'revenue')->orderBy('code')->get(),
            'suggestedNumber' => $invoice->number,
        ] + $this->websiteData());
    }

    public function update(Request $request, $id)
    {
        $invoice = AccSalesInvoice::findOrFail($id);

        if ($invoice->paid > 0) {
            return redirect(url('admin/accounting/sales'))->with('auth_errors', ['Faktur yang sudah dibayar tidak dapat diedit']);
        }

        $validated = $this->validateInvoice($request, $invoice->id);
        $totals = $this->computeTotals($request);

        DB::transaction(function () use ($validated, $request, $totals, $invoice) {
            $invoice->update([
                'number' => $validated['number'],
                'contact_id' => $validated['contact_id'],
                'date' => $validated['date'],
                'due_date' => $validated['due_date'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
            ]);

            $invoice->items()->delete();
            $this->saveItems($invoice, $request);

            // repost journal
            $this->poster->reverseForSource('invoice', $invoice->id);
            $this->postSalesJournal($invoice->fresh('items'));
        });

        return redirect(url('admin/accounting/sales'))->with('success', ['Faktur berhasil diperbarui']);
    }

    public function delete($id)
    {
        $invoice = AccSalesInvoice::findOrFail($id);

        DB::transaction(function () use ($invoice) {
            $this->poster->reverseForSource('invoice', $invoice->id);
            $this->poster->reverseForSource('invoice_payment', $invoice->id);
            $invoice->delete();
        });

        return redirect(url('admin/accounting/sales'))->with('success', ['Faktur berhasil dihapus']);
    }

    public function pay(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'account_id' => [
                'required',
                Rule::exists('acc_accounts', 'id')->where(fn ($query) => $query->where('is_cash', true)),
            ],
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($id, $validated) {
            $invoice = AccSalesInvoice::lockForUpdate()->findOrFail($id);
            $outstanding = (float) $invoice->total - (float) $invoice->paid;

            if ($validated['amount'] > $outstanding + 0.005) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran melebihi sisa tagihan (Rp '.number_format($outstanding, 0, ',', '.').')',
                ]);
            }

            $arAccount = $this->poster->accountId('1-10100'); // Piutang Usaha

            $this->poster->post([
                'date' => $validated['date'],
                'source' => 'invoice_payment',
                'source_id' => $invoice->id,
                'contact_id' => $invoice->contact_id,
                'reference' => $invoice->number,
                'description' => 'Pembayaran faktur '.$invoice->number,
                'number' => $this->poster->nextNumber('BKM'),
            ], [
                ['account_id' => $validated['account_id'], 'debit' => $validated['amount'], 'credit' => 0, 'memo' => 'Penerimaan pembayaran'],
                ['account_id' => $arAccount, 'debit' => 0, 'credit' => $validated['amount'], 'memo' => 'Pelunasan piutang'],
            ]);

            $paid = (float) $invoice->paid + (float) $validated['amount'];
            $invoice->update([
                'paid' => $paid,
                'status' => $paid >= (float) $invoice->total - 0.005 ? 'paid' : 'partial',
            ]);
        });

        return redirect(url('admin/accounting/sales/detail/'.$id))->with('success', ['Pembayaran berhasil dicatat']);
    }

    private function postSalesJournal(AccSalesInvoice $invoice): void
    {
        $arAccount = $this->poster->accountId('1-10100'); // Piutang Usaha
        $defaultRevenue = $this->poster->accountId('4-10001');
        $taxAccount = $this->poster->accountId('2-10004'); // PPN Keluaran

        $total = round((float) $invoice->total, 2);
        $tax = round((float) $invoice->tax, 2);
        $subtotal = (float) $invoice->subtotal;
        $discount = (float) $invoice->discount;

        // Net revenue target must equal total - tax (i.e. subtotal - discount)
        $netRevenueTarget = round($total - $tax, 2);

        $lines = [];
        // Debit AR for total
        $lines[] = ['account_id' => $arAccount, 'debit' => $total, 'credit' => 0, 'memo' => 'Piutang '.$invoice->number];

        // Credit revenue per item (group by account)
        $revenueByAccount = [];
        foreach ($invoice->items as $item) {
            $accId = $item->account_id ?: $defaultRevenue;
            $revenueByAccount[$accId] = ($revenueByAccount[$accId] ?? 0) + (float) $item->amount;
        }

        // Apply discount proportionally, rounding per line but forcing the last
        // line to absorb any rounding drift so the journal stays balanced.
        $keys = array_keys($revenueByAccount);
        $lastKey = end($keys);
        $accumulated = 0;
        foreach ($revenueByAccount as $accId => $amount) {
            if ($accId == $lastKey) {
                $net = round($netRevenueTarget - $accumulated, 2);
            } else {
                $net = $subtotal > 0 ? round($amount - ($discount * ($amount / $subtotal)), 2) : round($amount, 2);
                $accumulated += $net;
            }
            $lines[] = ['account_id' => $accId, 'debit' => 0, 'credit' => $net, 'memo' => 'Pendapatan'];
        }

        if ($tax > 0 && $taxAccount) {
            $lines[] = ['account_id' => $taxAccount, 'debit' => 0, 'credit' => $tax, 'memo' => 'PPN Keluaran'];
        }

        $this->poster->post([
            'date' => $invoice->date->toDateString(),
            'source' => 'invoice',
            'source_id' => $invoice->id,
            'contact_id' => $invoice->contact_id,
            'reference' => $invoice->number,
            'description' => 'Faktur penjualan '.$invoice->number,
            'number' => $this->poster->nextNumber('SJ'),
        ], $lines);
    }

    private function computeTotals(Request $request): array
    {
        $subtotal = 0;
        foreach ($request->input('items', []) as $item) {
            $subtotal += (float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0);
        }
        $discount = (float) $request->input('discount', 0);
        $tax = (float) $request->input('tax', 0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $subtotal - $discount + $tax,
        ];
    }

    private function saveItems(AccSalesInvoice $invoice, Request $request): void
    {
        foreach ($request->input('items', []) as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            if ($qty <= 0 && $price <= 0) {
                continue;
            }
            $invoice->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'account_id' => $item['account_id'] ?? null,
                'description' => $item['description'] ?? '-',
                'qty' => $qty,
                'price' => $price,
                'amount' => $qty * $price,
            ]);
        }
    }

    private function validateInvoice(Request $request, $ignoreId = null): array
    {
        $unique = 'unique:acc_sales_invoices,number'.($ignoreId ? ','.$ignoreId : '');

        return $request->validate([
            'number' => 'nullable|string|max:40|'.$unique,
            'contact_id' => 'required|exists:acc_contacts,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'tax' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.price' => 'required|numeric|min:0',
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $subtotal = collect($request->input('items', []))->sum(
                        fn (array $item): float => (float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0)
                    );

                    if ((float) $value > $subtotal) {
                        $fail('Diskon tidak boleh melebihi subtotal.');
                    }
                },
            ],
            'items.*.account_id' => 'nullable|exists:acc_accounts,id',
            'items.*.product_id' => 'nullable|exists:acc_products,id',
        ]);
    }
}
