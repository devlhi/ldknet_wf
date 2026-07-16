<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccContact;
use App\Models\AccProduct;
use App\Models\AccPurchaseBill;
use App\Models\Website;
use App\Services\Accounting\JournalPoster;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccPurchaseBillController extends Controller
{
    public function __construct(private JournalPoster $poster) {}

    private function emptyPaginator(Request $request, int $perPage = 25): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, 1, ['path' => $request->url(), 'query' => $request->query()]);
    }

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
        $prefix = 'BILL-'.now()->format('Ym').'-';
        $last = AccPurchaseBill::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $query = AccPurchaseBill::with('contact')->orderByDesc('date')->orderByDesc('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('q')) {
            $query->where('number', 'like', "%{$search}%");
        }

        $showData = $request->boolean('show_data');

        return view('admin.accounting.purchase-bills', [
            'title' => 'Tagihan Pembelian',
            'bills' => $showData ? $query->paginate(25)->withQueryString() : $this->emptyPaginator($request),
            'showData' => $showData,
            'status' => $status,
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function create()
    {
        return view('admin.accounting.purchase-bill-form', [
            'title' => 'Tagihan Pembelian Baru',
            'bill' => null,
            'contacts' => AccContact::where('is_active', true)->orderBy('name')->get(),
            'products' => AccProduct::where('is_active', true)->orderBy('name')->get(),
            'accounts' => AccAccount::whereIn('type', ['expense', 'asset'])->orderBy('code')->get(),
            'suggestedNumber' => $this->nextNumber(),
        ] + $this->websiteData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateBill($request);
        $totals = $this->computeTotals($request);

        DB::transaction(function () use ($validated, $request, $totals) {
            $bill = AccPurchaseBill::create([
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

            $this->saveItems($bill, $request);
            $this->postPurchaseJournal($bill->fresh('items'));
        });

        return redirect(url('admin/accounting/purchases'))->with('success', ['Tagihan pembelian berhasil dibuat']);
    }

    public function show($id)
    {
        $bill = AccPurchaseBill::with(['items', 'contact'])->findOrFail($id);

        return view('admin.accounting.purchase-bill-detail', [
            'title' => 'Tagihan '.$bill->number,
            'bill' => $bill,
            'cashAccounts' => AccAccount::where('is_cash', true)->orderBy('code')->get(),
        ] + $this->websiteData());
    }

    public function edit($id)
    {
        $bill = AccPurchaseBill::with('items')->findOrFail($id);

        if ($bill->paid > 0) {
            return redirect(url('admin/accounting/purchases'))->with('auth_errors', ['Tagihan yang sudah dibayar tidak dapat diedit']);
        }

        return view('admin.accounting.purchase-bill-form', [
            'title' => 'Edit Tagihan '.$bill->number,
            'bill' => $bill,
            'contacts' => AccContact::where('is_active', true)->orderBy('name')->get(),
            'products' => AccProduct::where('is_active', true)->orderBy('name')->get(),
            'accounts' => AccAccount::whereIn('type', ['expense', 'asset'])->orderBy('code')->get(),
            'suggestedNumber' => $bill->number,
        ] + $this->websiteData());
    }

    public function update(Request $request, $id)
    {
        $bill = AccPurchaseBill::findOrFail($id);

        if ($bill->paid > 0) {
            return redirect(url('admin/accounting/purchases'))->with('auth_errors', ['Tagihan yang sudah dibayar tidak dapat diedit']);
        }

        $validated = $this->validateBill($request, $bill->id);
        $totals = $this->computeTotals($request);

        DB::transaction(function () use ($validated, $request, $totals, $bill) {
            $bill->update([
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

            $bill->items()->delete();
            $this->saveItems($bill, $request);

            $this->poster->reverseForSource('bill', $bill->id);
            $this->postPurchaseJournal($bill->fresh('items'));
        });

        return redirect(url('admin/accounting/purchases'))->with('success', ['Tagihan berhasil diperbarui']);
    }

    public function delete($id)
    {
        $bill = AccPurchaseBill::findOrFail($id);

        DB::transaction(function () use ($bill) {
            $this->poster->reverseForSource('bill', $bill->id);
            $this->poster->reverseForSource('bill_payment', $bill->id);
            $bill->delete();
        });

        return redirect(url('admin/accounting/purchases'))->with('success', ['Tagihan berhasil dihapus']);
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
            $bill = AccPurchaseBill::lockForUpdate()->findOrFail($id);
            $outstanding = (float) $bill->total - (float) $bill->paid;

            if ($validated['amount'] > $outstanding + 0.005) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran melebihi sisa tagihan (Rp '.number_format($outstanding, 0, ',', '.').')',
                ]);
            }

            $apAccount = $this->poster->accountId('2-10001'); // Utang Usaha

            $this->poster->post([
                'date' => $validated['date'],
                'source' => 'bill_payment',
                'source_id' => $bill->id,
                'contact_id' => $bill->contact_id,
                'reference' => $bill->number,
                'description' => 'Pembayaran tagihan '.$bill->number,
                'number' => $this->poster->nextNumber('BKK'),
            ], [
                ['account_id' => $apAccount, 'debit' => $validated['amount'], 'credit' => 0, 'memo' => 'Pelunasan utang'],
                ['account_id' => $validated['account_id'], 'debit' => 0, 'credit' => $validated['amount'], 'memo' => 'Pengeluaran pembayaran'],
            ]);

            $paid = (float) $bill->paid + (float) $validated['amount'];
            $bill->update([
                'paid' => $paid,
                'status' => $paid >= (float) $bill->total - 0.005 ? 'paid' : 'partial',
            ]);
        });

        return redirect(url('admin/accounting/purchases/detail/'.$id))->with('success', ['Pembayaran berhasil dicatat']);
    }

    private function postPurchaseJournal(AccPurchaseBill $bill): void
    {
        $apAccount = $this->poster->accountId('2-10001'); // Utang Usaha
        $defaultExpense = $this->poster->accountId('6-20002'); // Beban Lain-lain
        $taxAccount = $this->poster->accountId('1-10500'); // PPN Masukan

        $total = round((float) $bill->total, 2);
        $tax = round((float) $bill->tax, 2);
        $subtotal = (float) $bill->subtotal;
        $discount = (float) $bill->discount;

        // Net expense target must equal total - tax (i.e. subtotal - discount)
        $netExpenseTarget = round($total - $tax, 2);

        $lines = [];

        $expenseByAccount = [];
        foreach ($bill->items as $item) {
            $accId = $item->account_id ?: $defaultExpense;
            $expenseByAccount[$accId] = ($expenseByAccount[$accId] ?? 0) + (float) $item->amount;
        }

        // Apply discount proportionally, forcing the last line to absorb any
        // rounding drift so the journal stays balanced against AP total.
        $keys = array_keys($expenseByAccount);
        $lastKey = end($keys);
        $accumulated = 0;
        foreach ($expenseByAccount as $accId => $amount) {
            if ($accId == $lastKey) {
                $net = round($netExpenseTarget - $accumulated, 2);
            } else {
                $net = $subtotal > 0 ? round($amount - ($discount * ($amount / $subtotal)), 2) : round($amount, 2);
                $accumulated += $net;
            }
            $lines[] = ['account_id' => $accId, 'debit' => $net, 'credit' => 0, 'memo' => 'Pembelian'];
        }

        if ($tax > 0 && $taxAccount) {
            $lines[] = ['account_id' => $taxAccount, 'debit' => $tax, 'credit' => 0, 'memo' => 'PPN Masukan'];
        }

        // Credit AP for total
        $lines[] = ['account_id' => $apAccount, 'debit' => 0, 'credit' => $total, 'memo' => 'Utang '.$bill->number];

        $this->poster->post([
            'date' => $bill->date->toDateString(),
            'source' => 'bill',
            'source_id' => $bill->id,
            'contact_id' => $bill->contact_id,
            'reference' => $bill->number,
            'description' => 'Tagihan pembelian '.$bill->number,
            'number' => $this->poster->nextNumber('PJ'),
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

    private function saveItems(AccPurchaseBill $bill, Request $request): void
    {
        foreach ($request->input('items', []) as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            if ($qty <= 0 && $price <= 0) {
                continue;
            }
            $bill->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'account_id' => $item['account_id'] ?? null,
                'description' => $item['description'] ?? '-',
                'qty' => $qty,
                'price' => $price,
                'amount' => $qty * $price,
            ]);
        }
    }

    private function validateBill(Request $request, $ignoreId = null): array
    {
        $unique = 'unique:acc_purchase_bills,number'.($ignoreId ? ','.$ignoreId : '');

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
