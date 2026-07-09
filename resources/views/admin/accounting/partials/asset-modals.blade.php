@php
    $fixedAssets = $assetAccounts->where('subtype', 'fixed_asset');
@endphp

<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ url('admin/accounting/assets/store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Aset Tetap</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <div class="col-md-4"><label class="form-label">Kode</label><input type="text" name="code" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label">Nama Aset</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Tgl Perolehan</label><input type="date" name="acquired_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-4"><label class="form-label">Harga Perolehan</label><input type="number" step="0.01" name="acquisition_cost" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Nilai Residu</label><input type="number" step="0.01" name="salvage_value" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Umur Manfaat (bulan)</label><input type="number" name="useful_life_months" class="form-control" value="60" required></div>
                    <div class="col-md-4"><label class="form-label">Akun Aset</label>
                        <select name="asset_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($fixedAssets as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun Akum. Penyusutan</label>
                        <select name="accum_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($fixedAssets as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun Beban Penyusutan</label>
                        <select name="expense_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($expenseAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="#" method="POST" id="editAssetForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Aset Tetap</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <div class="col-md-4"><label class="form-label">Kode</label><input type="text" name="code" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label">Nama Aset</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Tgl Perolehan</label><input type="date" name="acquired_date" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Harga Perolehan</label><input type="number" step="0.01" name="acquisition_cost" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Nilai Residu</label><input type="number" step="0.01" name="salvage_value" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label">Umur Manfaat (bulan)</label><input type="number" name="useful_life_months" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Akun Aset</label>
                        <select name="asset_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($fixedAssets as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun Akum. Penyusutan</label>
                        <select name="accum_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($fixedAssets as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Akun Beban Penyusutan</label>
                        <select name="expense_account_id" class="form-select"><option value="">-- Pilih --</option>
                            @foreach ($expenseAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
