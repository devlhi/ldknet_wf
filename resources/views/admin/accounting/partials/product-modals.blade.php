@php
    $incomeAccounts = $accounts->where('type', 'revenue');
    $expenseAccounts = $accounts->whereIn('type', ['expense']);
    $assetAccounts = $accounts->where('type', 'asset');
@endphp

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ url('admin/accounting/products/store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Produk / Jasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <div class="col-md-4"><label class="form-label">Kode</label><input type="text" name="code" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Tipe</label>
                        <select name="type" class="form-select"><option value="service">Jasa</option><option value="product">Barang</option></select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Satuan</label><input type="text" name="unit" class="form-control" placeholder="pcs, bulan"></div>
                    <div class="col-md-4"><label class="form-label">Stok Awal</label><input type="number" step="0.01" name="stock" class="form-control" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Harga Jual</label><input type="number" step="0.01" name="sale_price" class="form-control" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Harga Beli</label><input type="number" step="0.01" name="purchase_price" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Akun Pendapatan</label>
                        <select name="income_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($incomeAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun HPP/Beban</label>
                        <select name="expense_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($expenseAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun Persediaan</label>
                        <select name="inventory_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($assetAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="#" method="POST" id="editProductForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Produk / Jasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <div class="col-md-4"><label class="form-label">Kode</label><input type="text" name="code" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Tipe</label>
                        <select name="type" class="form-select"><option value="service">Jasa</option><option value="product">Barang</option></select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Satuan</label><input type="text" name="unit" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Stok</label><input type="number" step="0.01" name="stock" class="form-control" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Harga Jual</label><input type="number" step="0.01" name="sale_price" class="form-control" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Harga Beli</label><input type="number" step="0.01" name="purchase_price" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Akun Pendapatan</label>
                        <select name="income_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($incomeAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun HPP/Beban</label>
                        <select name="expense_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($expenseAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun Persediaan</label>
                        <select name="inventory_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($assetAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_p_active">
                            <label class="form-check-label" for="edit_p_active">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
