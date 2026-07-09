<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccAsset;
use App\Models\Website;
use App\Services\Accounting\JournalPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccAssetController extends Controller
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

    public function index(Request $request)
    {
        $query = AccAsset::orderByDesc('acquired_date')->orderByDesc('id');

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('admin.accounting.assets', [
            'title' => 'Aset Tetap',
            'assets' => $query->paginate(25)->withQueryString(),
            'assetAccounts' => AccAccount::where('type', 'asset')->orderBy('code')->get(),
            'expenseAccounts' => AccAccount::where('type', 'expense')->orderBy('code')->get(),
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateAsset($request);
        $validated['status'] = 'active';
        $validated['accumulated_depreciation'] = 0;
        AccAsset::create($validated);

        return redirect(url('admin/accounting/assets'))->with('success', ['Aset berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $asset = AccAsset::findOrFail($id);
        $validated = $this->validateAsset($request);
        $asset->update($validated);

        return redirect(url('admin/accounting/assets'))->with('success', ['Aset berhasil diperbarui']);
    }

    public function delete($id)
    {
        $asset = AccAsset::findOrFail($id);

        DB::transaction(function () use ($asset) {
            $this->poster->reverseForSource('depreciation', $asset->id);
            $asset->delete();
        });

        return redirect(url('admin/accounting/assets'))->with('success', ['Aset berhasil dihapus']);
    }

    public function show($id)
    {
        $asset = AccAsset::with('depreciations')->findOrFail($id);

        return view('admin.accounting.asset-detail', [
            'title' => 'Aset '.$asset->name,
            'asset' => $asset,
        ] + $this->websiteData());
    }

    /**
     * Record one period of depreciation and post the journal.
     */
    public function depreciate(Request $request, $id)
    {
        $asset = AccAsset::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if (! $asset->expense_account_id || ! $asset->accum_account_id) {
            return redirect()->back()->with('auth_errors', ['Akun beban penyusutan dan akun akumulasi harus diatur dulu pada aset']);
        }

        $bookValue = (float) $asset->acquisition_cost - (float) $asset->accumulated_depreciation;
        $depreciable = $bookValue - (float) $asset->salvage_value;

        if ($depreciable <= 0.005) {
            return redirect()->back()->with('auth_errors', ['Aset sudah tersusut penuh']);
        }

        $amount = (float) ($validated['amount'] ?? 0);
        if ($amount <= 0) {
            $amount = $asset->monthly_depreciation;
        }
        // cap at remaining depreciable value
        $amount = min($amount, $depreciable);

        DB::transaction(function () use ($asset, $validated, $amount) {
            $journal = $this->poster->post([
                'date' => $validated['date'],
                'source' => 'depreciation',
                'source_id' => $asset->id,
                'reference' => $asset->code ?: $asset->name,
                'description' => 'Penyusutan aset '.$asset->name,
                'number' => $this->poster->nextNumber('DEP'),
            ], [
                ['account_id' => $asset->expense_account_id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Beban penyusutan'],
                ['account_id' => $asset->accum_account_id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Akumulasi penyusutan'],
            ]);

            $asset->depreciations()->create([
                'date' => $validated['date'],
                'amount' => $amount,
                'journal_id' => $journal->id,
            ]);

            $asset->increment('accumulated_depreciation', $amount);
        });

        return redirect(url('admin/accounting/assets/detail/'.$asset->id))->with('success', ['Penyusutan berhasil dicatat: Rp '.number_format($amount, 0, ',', '.')]);
    }

    private function validateAsset(Request $request): array
    {
        return $request->validate([
            'code' => 'nullable|string|max:40',
            'name' => 'required|string|max:150',
            'acquired_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'asset_account_id' => 'nullable|exists:acc_accounts,id',
            'accum_account_id' => 'nullable|exists:acc_accounts,id',
            'expense_account_id' => 'nullable|exists:acc_accounts,id',
            'notes' => 'nullable|string',
        ]);
    }
}
