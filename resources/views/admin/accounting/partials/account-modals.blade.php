@php
    $subtypeOptions = [
        'current_asset' => 'Aset Lancar',
        'fixed_asset' => 'Aset Tetap',
        'current_liability' => 'Liabilitas Jangka Pendek',
        'long_liability' => 'Liabilitas Jangka Panjang',
        'equity' => 'Ekuitas',
        'operating_revenue' => 'Pendapatan Operasional',
        'other_revenue' => 'Pendapatan Lain',
        'contra_revenue' => 'Kontra Pendapatan',
        'cogs' => 'HPP',
        'operating_expense' => 'Beban Operasional',
        'other_expense' => 'Beban Lain',
    ];
@endphp

<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('admin/accounting/accounts/store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Kode Akun</label>
                        <input type="text" name="code" class="form-control" required placeholder="cth: 1-10010">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nama Akun</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select" required>
                            <option value="asset">Aset</option>
                            <option value="liability">Liabilitas</option>
                            <option value="equity">Ekuitas</option>
                            <option value="revenue">Pendapatan</option>
                            <option value="expense">Beban</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Sub Tipe</label>
                        <select name="subtype" class="form-select">
                            <option value="">-- Tidak ada --</option>
                            @foreach ($subtypeOptions as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Saldo Awal</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_cash" id="add_is_cash">
                        <label class="form-check-label" for="add_is_cash">Akun Kas / Bank (masuk laporan arus kas)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST" id="editAccountForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Kode Akun</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nama Akun</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select" required>
                            <option value="asset">Aset</option>
                            <option value="liability">Liabilitas</option>
                            <option value="equity">Ekuitas</option>
                            <option value="revenue">Pendapatan</option>
                            <option value="expense">Beban</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Sub Tipe</label>
                        <select name="subtype" class="form-select">
                            <option value="">-- Tidak ada --</option>
                            @foreach ($subtypeOptions as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Saldo Awal</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="0">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_cash" id="edit_is_cash">
                        <label class="form-check-label" for="edit_is_cash">Akun Kas / Bank</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">Aktif</label>
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
