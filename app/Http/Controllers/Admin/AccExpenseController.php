<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccContact;
use App\Models\AccExpense;
use App\Models\Website;
use App\Services\Accounting\JournalPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccExpenseController extends Controller
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
        $prefix = 'EXP-'.now()->format('Ym').'-';
        $last = AccExpense::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $query = AccExpense::orderByDesc('date')->orderByDesc('id');

        if ($start = $request->input('start')) {
            $query->where('date', '>=', $start);
        }
        if ($end = $request->input('end')) {
            $query->where('date', '<=', $end);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return view('admin.accounting.expenses', [
            'title' => 'Biaya / Pengeluaran',
            'expenses' => $query->paginate(25)->withQueryString(),
            'expenseAccounts' => AccAccount::where('type', 'expense')->orderBy('code')->get(),
            'cashAccounts' => AccAccount::where('is_cash', true)->orderBy('code')->get(),
            'contacts' => AccContact::where('is_active', true)->orderBy('name')->get(),
            'start' => $request->input('start'),
            'end' => $request->input('end'),
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($validated, $request) {
            $expense = AccExpense::create([
                'number' => $request->input('number') ?: $this->nextNumber(),
                'date' => $validated['date'],
                'contact_id' => $validated['contact_id'] ?? null,
                'expense_account_id' => $validated['expense_account_id'],
                'payment_account_id' => $validated['payment_account_id'],
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $validated['amount'],
                'created_by' => auth()->id(),
            ]);

            $this->postJournal($expense);
        });

        return redirect(url('admin/accounting/expenses'))->with('success', ['Biaya berhasil dicatat']);
    }

    public function update(Request $request, $id)
    {
        $expense = AccExpense::findOrFail($id);
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($validated, $expense) {
            $expense->update([
                'date' => $validated['date'],
                'contact_id' => $validated['contact_id'] ?? null,
                'expense_account_id' => $validated['expense_account_id'],
                'payment_account_id' => $validated['payment_account_id'],
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $validated['amount'],
            ]);

            $this->poster->reverseForSource('expense', $expense->id);
            $this->postJournal($expense);
        });

        return redirect(url('admin/accounting/expenses'))->with('success', ['Biaya berhasil diperbarui']);
    }

    public function delete($id)
    {
        $expense = AccExpense::findOrFail($id);

        DB::transaction(function () use ($expense) {
            $this->poster->reverseForSource('expense', $expense->id);
            $expense->delete();
        });

        return redirect(url('admin/accounting/expenses'))->with('success', ['Biaya berhasil dihapus']);
    }

    private function postJournal(AccExpense $expense): void
    {
        $this->poster->post([
            'date' => $expense->date->toDateString(),
            'source' => 'expense',
            'source_id' => $expense->id,
            'contact_id' => $expense->contact_id,
            'reference' => $expense->reference,
            'description' => $expense->description ?: 'Biaya '.$expense->number,
            'number' => $this->poster->nextNumber('BK'),
        ], [
            ['account_id' => $expense->expense_account_id, 'debit' => (float) $expense->amount, 'credit' => 0, 'memo' => $expense->description],
            ['account_id' => $expense->payment_account_id, 'debit' => 0, 'credit' => (float) $expense->amount, 'memo' => 'Pembayaran biaya'],
        ]);
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'date' => 'required|date',
            'contact_id' => 'nullable|exists:acc_contacts,id',
            'expense_account_id' => 'required|exists:acc_accounts,id',
            'payment_account_id' => 'required|exists:acc_accounts,id',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
        ]);
    }
}
