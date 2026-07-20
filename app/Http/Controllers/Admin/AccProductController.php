<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccProduct;
use App\Models\Website;
use Illuminate\Http\Request;

class AccProductController extends Controller
{
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
        $query = AccProduct::query()->orderBy('name');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return view('admin.accounting.products', [
            'title' => 'Produk & Jasa',
            'products' => $query->paginate(25)->withQueryString(),
            'accounts' => AccAccount::where('is_active', true)->orderBy('code')->get(),
            'filterType' => $type,
            'search' => $request->input('q'),
        ] + $this->websiteData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);
        $validated['is_active'] = true;
        AccProduct::create($validated);

        return redirect(url('admin/accounting/products'))->with('success', ['Produk berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $product = AccProduct::findOrFail($id);
        $validated = $this->validateProduct($request);
        $validated['is_active'] = $request->has('is_active');
        $product->update($validated);

        return redirect(url('admin/accounting/products'))->with('success', ['Produk berhasil diperbarui']);
    }

    public function delete($id)
    {
        AccProduct::findOrFail($id)->delete();

        return redirect(url('admin/accounting/products'))->with('success', ['Produk berhasil dihapus']);
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'code' => 'nullable|string|max:40',
            'name' => 'required|string|max:150',
            'type' => 'required|in:service,product',
            'unit' => 'nullable|string|max:30',
            'sale_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|numeric',
            'income_account_id' => 'nullable|exists:acc_accounts,id',
            'expense_account_id' => 'nullable|exists:acc_accounts,id',
            'inventory_account_id' => 'nullable|exists:acc_accounts,id',
            'description' => 'nullable|string',
        ]);
    }
}
