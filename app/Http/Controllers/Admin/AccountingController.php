<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccContact;
use App\Models\AccJournal;
use App\Models\AccJournalLine;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    /**
     * Compute posted balance (debit - credit signed by normal balance) per account
     * within an optional date range. Returns keyed by account_id.
     */
    private function accountBalances(?string $start = null, ?string $end = null)
    {
        $query = AccJournalLine::query()
            ->join('acc_journals', 'acc_journals.id', '=', 'acc_journal_lines.journal_id')
            ->where('acc_journals.is_posted', true)
            ->select(
                'acc_journal_lines.account_id',
                DB::raw('SUM(acc_journal_lines.debit) as total_debit'),
                DB::raw('SUM(acc_journal_lines.credit) as total_credit')
            )
            ->groupBy('acc_journal_lines.account_id');

        if ($start) {
            $query->where('acc_journals.date', '>=', $start);
        }
        if ($end) {
            $query->where('acc_journals.date', '<=', $end);
        }

        return $query->get()->keyBy('account_id');
    }

    public function index()
    {
        $accountCount = AccAccount::count();
        $journalCount = AccJournal::count();
        $contactCount = AccContact::count();

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();
        $balances = $this->accountBalances($start, $end);

        $income = 0;
        $expense = 0;
        $cash = 0;
        $allBalances = $this->accountBalances(null, $end);

        foreach (AccAccount::all() as $acc) {
            $b = $balances->get($acc->id);
            if ($b) {
                if ($acc->type === 'revenue') {
                    $income += ((float) $b->total_credit - (float) $b->total_debit);
                } elseif ($acc->type === 'expense') {
                    $expense += ((float) $b->total_debit - (float) $b->total_credit);
                }
            }

            if ($acc->is_cash) {
                $ab = $allBalances->get($acc->id);
                if ($ab) {
                    $cash += ((float) $ab->total_debit - (float) $ab->total_credit);
                }
                $cash += (float) $acc->opening_balance;
            }
        }

        $recentJournals = AccJournal::orderByDesc('date')->orderByDesc('id')->limit(10)->get();

        return view('admin.accounting.index', [
            'title' => 'Dashboard Akuntansi',
            'accountCount' => $accountCount,
            'journalCount' => $journalCount,
            'contactCount' => $contactCount,
            'income' => $income,
            'expense' => $expense,
            'netProfit' => $income - $expense,
            'cash' => $cash,
            'recentJournals' => $recentJournals,
        ] + $this->websiteData());
    }

    // ===================== CHART OF ACCOUNTS =====================

    public function accounts(Request $request)
    {
        $query = AccAccount::query()->orderBy('code');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->get();
        $balances = $this->accountBalances();

        return view('admin.accounting.accounts', [
            'title' => 'Daftar Akun (Chart of Accounts)',
            'accounts' => $accounts,
            'balances' => $balances,
            'filterType' => $type,
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function accountStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:acc_accounts,code',
            'name' => 'required|string|max:150',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'nullable|string|max:50',
            'is_cash' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $validated['is_cash'] = $request->has('is_cash');
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = true;

        AccAccount::create($validated);

        return redirect(url('admin/accounting/accounts'))->with('success', ['Akun berhasil ditambahkan']);
    }

    public function accountUpdate(Request $request, $id)
    {
        $account = AccAccount::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:acc_accounts,code,'.$account->id,
            'name' => 'required|string|max:150',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'nullable|string|max:50',
            'is_cash' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_cash'] = $request->has('is_cash');
        $validated['is_active'] = $request->has('is_active');
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;

        $account->update($validated);

        return redirect(url('admin/accounting/accounts'))->with('success', ['Akun berhasil diperbarui']);
    }

    public function accountDelete($id)
    {
        $account = AccAccount::findOrFail($id);

        if ($account->is_locked) {
            return redirect(url('admin/accounting/accounts'))->with('auth_errors', ['Akun sistem tidak dapat dihapus']);
        }

        if ($account->lines()->exists()) {
            return redirect(url('admin/accounting/accounts'))->with('auth_errors', ['Akun sudah dipakai di jurnal, tidak dapat dihapus']);
        }

        $account->delete();

        return redirect(url('admin/accounting/accounts'))->with('success', ['Akun berhasil dihapus']);
    }

    // ===================== JURNAL UMUM =====================

    private function generateJournalNumber(): string
    {
        $prefix = 'JV-'.now()->format('Ym').'-';
        $last = AccJournal::where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function journals(Request $request)
    {
        $query = AccJournal::query()->orderByDesc('date')->orderByDesc('id');

        if ($start = $request->input('start')) {
            $query->where('date', '>=', $start);
        }
        if ($end = $request->input('end')) {
            $query->where('date', '<=', $end);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $journals = $query->paginate(25)->withQueryString();

        return view('admin.accounting.journals', [
            'title' => 'Jurnal Umum',
            'journals' => $journals,
            'start' => $request->input('start'),
            'end' => $request->input('end'),
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function journalCreate()
    {
        return view('admin.accounting.journal-form', [
            'title' => 'Buat Jurnal Baru',
            'journal' => null,
            'accounts' => AccAccount::where('is_active', true)->orderBy('code')->get(),
            'contacts' => AccContact::where('is_active', true)->orderBy('name')->get(),
            'suggestedNumber' => $this->generateJournalNumber(),
        ] + $this->websiteData());
    }

    public function journalStore(Request $request)
    {
        $validated = $this->validateJournal($request);

        DB::transaction(function () use ($validated, $request) {
            $journal = AccJournal::create([
                'number' => $validated['number'] ?: $this->generateJournalNumber(),
                'date' => $validated['date'],
                'source' => 'manual',
                'contact_id' => $validated['contact_id'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'total' => $this->linesTotal($request),
                'is_posted' => true,
                'created_by' => auth()->id(),
            ]);

            $this->saveLines($journal, $request);
        });

        return redirect(url('admin/accounting/journals'))->with('success', ['Jurnal berhasil disimpan']);
    }

    public function journalShow($id)
    {
        $journal = AccJournal::with(['lines.account', 'contact'])->findOrFail($id);

        return view('admin.accounting.journal-detail', [
            'title' => 'Detail Jurnal '.$journal->number,
            'journal' => $journal,
        ] + $this->websiteData());
    }

    public function journalEdit($id)
    {
        $journal = AccJournal::with('lines')->findOrFail($id);

        if ($journal->source !== 'manual') {
            return redirect(url('admin/accounting/journals'))->with('auth_errors', ['Jurnal otomatis dari transaksi tidak dapat diedit langsung']);
        }

        return view('admin.accounting.journal-form', [
            'title' => 'Edit Jurnal '.$journal->number,
            'journal' => $journal,
            'accounts' => AccAccount::where('is_active', true)->orderBy('code')->get(),
            'contacts' => AccContact::where('is_active', true)->orderBy('name')->get(),
            'suggestedNumber' => $journal->number,
        ] + $this->websiteData());
    }

    public function journalUpdate(Request $request, $id)
    {
        $journal = AccJournal::findOrFail($id);

        if ($journal->source !== 'manual') {
            return redirect(url('admin/accounting/journals'))->with('auth_errors', ['Jurnal otomatis tidak dapat diedit']);
        }

        $validated = $this->validateJournal($request, $journal->id);

        DB::transaction(function () use ($validated, $request, $journal) {
            $journal->update([
                'number' => $validated['number'],
                'date' => $validated['date'],
                'contact_id' => $validated['contact_id'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? null,
                'total' => $this->linesTotal($request),
            ]);

            $journal->lines()->delete();
            $this->saveLines($journal, $request);
        });

        return redirect(url('admin/accounting/journals'))->with('success', ['Jurnal berhasil diperbarui']);
    }

    public function journalDelete($id)
    {
        $journal = AccJournal::findOrFail($id);

        if ($journal->source !== 'manual') {
            return redirect(url('admin/accounting/journals'))->with('auth_errors', ['Jurnal otomatis tidak dapat dihapus']);
        }

        $journal->delete();

        return redirect(url('admin/accounting/journals'))->with('success', ['Jurnal berhasil dihapus']);
    }

    private function validateJournal(Request $request, $ignoreId = null): array
    {
        $unique = 'unique:acc_journals,number'.($ignoreId ? ','.$ignoreId : '');

        $validated = $request->validate([
            'number' => 'nullable|string|max:40|'.$unique,
            'date' => 'required|date',
            'contact_id' => 'nullable|exists:acc_contacts,id',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:acc_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:255',
        ]);

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($request->input('lines', []) as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.005) {
            abort(redirect()->back()->withInput()->with('auth_errors', ['Total debit dan kredit harus seimbang (balance)']));
        }

        if (round($totalDebit, 2) <= 0) {
            abort(redirect()->back()->withInput()->with('auth_errors', ['Nilai jurnal tidak boleh nol']));
        }

        return $validated;
    }

    private function linesTotal(Request $request): float
    {
        $total = 0;
        foreach ($request->input('lines', []) as $line) {
            $total += (float) ($line['debit'] ?? 0);
        }

        return $total;
    }

    private function saveLines(AccJournal $journal, Request $request): void
    {
        foreach ($request->input('lines', []) as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit == 0 && $credit == 0) {
                continue;
            }

            $journal->lines()->create([
                'account_id' => $line['account_id'],
                'memo' => $line['memo'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
            ]);
        }
    }

    // ===================== KONTAK (Customer / Vendor) =====================

    public function contacts(Request $request)
    {
        $query = AccContact::query()->orderBy('name');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.accounting.contacts', [
            'title' => 'Kontak',
            'contacts' => $query->paginate(25)->withQueryString(),
            'filterType' => $type,
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function contactStore(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:customer,vendor,both,employee',
            'code' => 'nullable|string|max:30',
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:40',
            'tax_number' => 'nullable|string|max:40',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);

        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = true;

        AccContact::create($validated);

        return redirect(url('admin/accounting/contacts'))->with('success', ['Kontak berhasil ditambahkan']);
    }

    public function contactUpdate(Request $request, $id)
    {
        $contact = AccContact::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:customer,vendor,both,employee',
            'code' => 'nullable|string|max:30',
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:40',
            'tax_number' => 'nullable|string|max:40',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = $request->has('is_active');

        $contact->update($validated);

        return redirect(url('admin/accounting/contacts'))->with('success', ['Kontak berhasil diperbarui']);
    }

    public function contactDelete($id)
    {
        $contact = AccContact::findOrFail($id);
        $contact->delete();

        return redirect(url('admin/accounting/contacts'))->with('success', ['Kontak berhasil dihapus']);
    }

    // ===================== LAPORAN: BUKU BESAR =====================

    public function reportLedger(Request $request)
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());
        $accountId = $request->input('account_id');

        $accounts = AccAccount::orderBy('code')->get();
        $ledger = null;
        $selectedAccount = null;

        if ($accountId) {
            $selectedAccount = AccAccount::findOrFail($accountId);

            // Opening balance = opening_balance + movements before start date
            $before = AccJournalLine::query()
                ->join('acc_journals', 'acc_journals.id', '=', 'acc_journal_lines.journal_id')
                ->where('acc_journals.is_posted', true)
                ->where('acc_journal_lines.account_id', $accountId)
                ->where('acc_journals.date', '<', $start)
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();

            $sign = $selectedAccount->isDebitNormal() ? 1 : -1;
            $opening = (float) $selectedAccount->opening_balance
                + $sign * ((float) $before->d - (float) $before->c);

            $lines = AccJournalLine::query()
                ->join('acc_journals', 'acc_journals.id', '=', 'acc_journal_lines.journal_id')
                ->where('acc_journals.is_posted', true)
                ->where('acc_journal_lines.account_id', $accountId)
                ->whereBetween('acc_journals.date', [$start, $end])
                ->orderBy('acc_journals.date')
                ->orderBy('acc_journals.id')
                ->select(
                    'acc_journal_lines.*',
                    'acc_journals.number as j_number',
                    'acc_journals.date as j_date',
                    'acc_journals.description as j_description'
                )
                ->get();

            $running = $opening;
            $rows = [];
            foreach ($lines as $line) {
                $running += $sign * ((float) $line->debit - (float) $line->credit);
                $rows[] = [
                    'date' => $line->j_date,
                    'number' => $line->j_number,
                    'memo' => $line->memo ?: $line->j_description,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'balance' => $running,
                ];
            }

            $ledger = [
                'opening' => $opening,
                'rows' => $rows,
                'closing' => $running,
            ];
        }

        return view('admin.accounting.report-ledger', [
            'title' => 'Buku Besar',
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'ledger' => $ledger,
            'start' => $start,
            'end' => $end,
        ] + $this->websiteData());
    }

    // ===================== LAPORAN: NERACA SALDO =====================

    public function reportTrialBalance(Request $request)
    {
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        $balances = $this->accountBalances(null, $end);
        $accounts = AccAccount::orderBy('code')->get();

        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $acc) {
            $b = $balances->get($acc->id);
            $movement = $b ? ((float) $b->total_debit - (float) $b->total_credit) : 0;
            $net = (float) $acc->opening_balance + ($acc->isDebitNormal() ? $movement : -$movement);

            if (abs($net) < 0.005) {
                continue;
            }

            // Present according to normal balance
            $debit = 0;
            $credit = 0;
            if ($acc->isDebitNormal()) {
                $net >= 0 ? $debit = $net : $credit = abs($net);
            } else {
                $net >= 0 ? $credit = $net : $debit = abs($net);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
            $rows[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return view('admin.accounting.report-trial-balance', [
            'title' => 'Neraca Saldo',
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'end' => $end,
        ] + $this->websiteData());
    }

    // ===================== LAPORAN: LABA RUGI =====================

    public function reportProfitLoss(Request $request)
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        $balances = $this->accountBalances($start, $end);

        $revenue = [];
        $cogs = [];
        $expense = [];
        $totalRevenue = 0;
        $totalCogs = 0;
        $totalExpense = 0;

        foreach (AccAccount::orderBy('code')->get() as $acc) {
            $b = $balances->get($acc->id);
            if (! $b) {
                continue;
            }

            if ($acc->type === 'revenue') {
                $amount = (float) $b->total_credit - (float) $b->total_debit;
                if (abs($amount) < 0.005) {
                    continue;
                }
                $revenue[] = ['name' => $acc->name, 'amount' => $amount];
                $totalRevenue += $amount;
            } elseif ($acc->type === 'expense') {
                $amount = (float) $b->total_debit - (float) $b->total_credit;
                if (abs($amount) < 0.005) {
                    continue;
                }
                if ($acc->subtype === 'cogs') {
                    $cogs[] = ['name' => $acc->name, 'amount' => $amount];
                    $totalCogs += $amount;
                } else {
                    $expense[] = ['name' => $acc->name, 'amount' => $amount];
                    $totalExpense += $amount;
                }
            }
        }

        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpense;

        return view('admin.accounting.report-profit-loss', [
            'title' => 'Laporan Laba Rugi',
            'revenue' => $revenue,
            'cogs' => $cogs,
            'expense' => $expense,
            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'totalExpense' => $totalExpense,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'start' => $start,
            'end' => $end,
        ] + $this->websiteData());
    }

    // ===================== LAPORAN: NERACA (BALANCE SHEET) =====================

    public function reportBalanceSheet(Request $request)
    {
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        $balances = $this->accountBalances(null, $end);

        $assets = [];
        $liabilities = [];
        $equity = [];
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        // Net profit up to end date (revenue - expense) flows into equity
        $netProfit = 0;

        foreach (AccAccount::orderBy('code')->get() as $acc) {
            $b = $balances->get($acc->id);
            $movement = $b ? ((float) $b->total_debit - (float) $b->total_credit) : 0;

            if ($acc->type === 'asset') {
                $amount = (float) $acc->opening_balance + $movement;
                if (abs($amount) < 0.005) {
                    continue;
                }
                $assets[] = ['name' => $acc->name, 'amount' => $amount];
                $totalAssets += $amount;
            } elseif ($acc->type === 'liability') {
                $amount = (float) $acc->opening_balance + (-$movement);
                if (abs($amount) < 0.005) {
                    continue;
                }
                $liabilities[] = ['name' => $acc->name, 'amount' => $amount];
                $totalLiabilities += $amount;
            } elseif ($acc->type === 'equity') {
                $amount = (float) $acc->opening_balance + (-$movement);
                if (abs($amount) >= 0.005) {
                    $equity[] = ['name' => $acc->name, 'amount' => $amount];
                    $totalEquity += $amount;
                }
            } elseif ($acc->type === 'revenue') {
                $netProfit += (-$movement);
            } elseif ($acc->type === 'expense') {
                $netProfit -= $movement;
            }
        }

        // Add current period profit to equity section
        $equity[] = ['name' => 'Laba (Rugi) Berjalan', 'amount' => $netProfit];
        $totalEquity += $netProfit;

        return view('admin.accounting.report-balance-sheet', [
            'title' => 'Neraca (Balance Sheet)',
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'totalLiabEquity' => $totalLiabilities + $totalEquity,
            'end' => $end,
        ] + $this->websiteData());
    }

    // ===================== LAPORAN: ARUS KAS =====================

    public function reportCashFlow(Request $request)
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());

        $cashAccounts = AccAccount::where('is_cash', true)->orderBy('code')->get();
        $cashIds = $cashAccounts->pluck('id')->all();

        $rows = [];
        $openingTotal = 0;
        $inflow = 0;
        $outflow = 0;

        foreach ($cashAccounts as $acc) {
            // opening = opening_balance + movement before start
            $before = AccJournalLine::query()
                ->join('acc_journals', 'acc_journals.id', '=', 'acc_journal_lines.journal_id')
                ->where('acc_journals.is_posted', true)
                ->where('acc_journal_lines.account_id', $acc->id)
                ->where('acc_journals.date', '<', $start)
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();

            $openingTotal += (float) $acc->opening_balance + ((float) $before->d - (float) $before->c);
        }

        // Movements within period on cash accounts
        $movements = AccJournalLine::query()
            ->join('acc_journals', 'acc_journals.id', '=', 'acc_journal_lines.journal_id')
            ->where('acc_journals.is_posted', true)
            ->whereIn('acc_journal_lines.account_id', $cashIds)
            ->whereBetween('acc_journals.date', [$start, $end])
            ->orderBy('acc_journals.date')
            ->orderBy('acc_journals.id')
            ->select(
                'acc_journal_lines.debit',
                'acc_journal_lines.credit',
                'acc_journal_lines.memo',
                'acc_journals.number as j_number',
                'acc_journals.date as j_date',
                'acc_journals.description as j_description'
            )
            ->get();

        foreach ($movements as $m) {
            $in = (float) $m->debit;
            $out = (float) $m->credit;
            $inflow += $in;
            $outflow += $out;
            $rows[] = [
                'date' => $m->j_date,
                'number' => $m->j_number,
                'memo' => $m->memo ?: $m->j_description,
                'in' => $in,
                'out' => $out,
            ];
        }

        return view('admin.accounting.report-cash-flow', [
            'title' => 'Laporan Arus Kas',
            'rows' => $rows,
            'opening' => $openingTotal,
            'inflow' => $inflow,
            'outflow' => $outflow,
            'closing' => $openingTotal + $inflow - $outflow,
            'start' => $start,
            'end' => $end,
        ] + $this->websiteData());
    }
}
