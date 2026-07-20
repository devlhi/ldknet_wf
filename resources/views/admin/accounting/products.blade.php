@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-box me-1"></i> Produk & Jasa</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="uil uil-plus"></i> Tambah</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select name="type" class="form-select form-select-sm">
                                    <option value="">-- Semua Tipe --</option>
                                    <option value="service" {{ $filterType === 'service' ? 'selected' : '' }}>Jasa</option>
                                    <option value="product" {{ $filterType === 'product' ? 'selected' : '' }}>Barang</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nama / kode...">
                            </div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary"><i class="uil uil-filter"></i> Terapkan Filter</button></div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Tipe</th>
                                        <th>Satuan</th>
                                        <th class="text-end">Harga Jual</th>
                                        <th class="text-end">Harga Beli</th>
                                        <th class="text-end">Stok</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($products as $p)
                                        <tr>
                                            <td>{{ $p->code ?: '-' }}</td>
                                            <td>{{ $p->name }}</td>
                                            <td><span class="badge bg-light text-dark">{{ $p->type === 'service' ? 'Jasa' : 'Barang' }}</span></td>
                                            <td>{{ $p->unit ?: '-' }}</td>
                                            <td class="text-end">Rp {{ number_format($p->sale_price, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($p->purchase_price, 0, ',', '.') }}</td>
                                            <td class="text-end">{{ $p->type === 'product' ? number_format($p->stock, 0, ',', '.') : '-' }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                                    data-id="{{ $p->id }}"
                                                    data-code="{{ $p->code }}"
                                                    data-name="{{ $p->name }}"
                                                    data-type="{{ $p->type }}"
                                                    data-unit="{{ $p->unit }}"
                                                    data-sale="{{ $p->sale_price }}"
                                                    data-purchase="{{ $p->purchase_price }}"
                                                    data-stock="{{ $p->stock }}"
                                                    data-income="{{ $p->income_account_id }}"
                                                    data-expense="{{ $p->expense_account_id }}"
                                                    data-inventory="{{ $p->inventory_account_id }}"
                                                    data-active="{{ $p->is_active ? 1 : 0 }}"
                                                    data-bs-toggle="modal" data-bs-target="#editProductModal">
                                                    <i class="uil uil-edit"></i>
                                                </button>
                                                <a href="{{ url('admin/accounting/products/delete/'.$p->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus produk ini?')"><i class="uil uil-trash"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted">Belum ada produk</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.accounting.partials.product-modals')
@endsection

@section('scripts')
<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var f = document.getElementById('editProductForm');
        f.action = "{{ url('admin/accounting/products/update') }}/" + this.dataset.id;
        f.querySelector('[name=code]').value = this.dataset.code || '';
        f.querySelector('[name=name]').value = this.dataset.name;
        f.querySelector('[name=type]').value = this.dataset.type;
        f.querySelector('[name=unit]').value = this.dataset.unit || '';
        f.querySelector('[name=sale_price]').value = this.dataset.sale;
        f.querySelector('[name=purchase_price]').value = this.dataset.purchase;
        f.querySelector('[name=stock]').value = this.dataset.stock;
        f.querySelector('[name=income_account_id]').value = this.dataset.income || '';
        f.querySelector('[name=expense_account_id]').value = this.dataset.expense || '';
        f.querySelector('[name=inventory_account_id]').value = this.dataset.inventory || '';
        f.querySelector('[name=is_active]').checked = this.dataset.active === '1';
    });
});
</script>
@endsection
