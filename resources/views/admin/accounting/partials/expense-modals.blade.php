@php
    $expNumber = 'EXP-'.now()->format('Ym').'-';
@endphp

<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('admin/accounting/expenses/store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Catat Biaya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="mb-2"><label class="form-label">Akun Beban</label>
                        <select name="expense_account_id" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($expenseAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Dibayar dari (Kas/Bank)</label>
                        <select name="payment_account_id" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($cashAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Pemasok (opsional)</label>
                        <select name="contact_id" class="form-select">
                            <option value="">-- Tidak ada --</option>
                            @foreach ($contacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Jumlah</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Referensi</label><input type="text" name="reference" class="form-control"></div>
                    <div class="mb-2"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST" id="editExpenseForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Biaya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Akun Beban</label>
                        <select name="expense_account_id" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($expenseAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Dibayar dari (Kas/Bank)</label>
                        <select name="payment_account_id" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($cashAccounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Pemasok (opsional)</label>
                        <select name="contact_id" class="form-select">
                            <option value="">-- Tidak ada --</option>
                            @foreach ($contacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Jumlah</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Referensi</label><input type="text" name="reference" class="form-control"></div>
                    <div class="mb-2"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
