@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        @php
            $isEdit = $journal !== null;
            $action = $isEdit ? url('admin/accounting/journals/update/'.$journal->id) : url('admin/accounting/journals/store');
            $oldLines = old('lines');
            if (! $oldLines && $isEdit) {
                $oldLines = $journal->lines->map(fn ($l) => [
                    'account_id' => $l->account_id,
                    'memo' => $l->memo,
                    'debit' => (float) $l->debit,
                    'credit' => (float) $l->credit,
                ])->toArray();
            }
            if (! $oldLines) {
                $oldLines = [['account_id' => '', 'memo' => '', 'debit' => '', 'credit' => ''], ['account_id' => '', 'memo' => '', 'debit' => '', 'credit' => '']];
            }
        @endphp

        <div class="row">
            <div class="col-lg-11 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0"><i class="uil uil-book me-1"></i> {{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ $action }}" method="POST" id="journalForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Nomor Jurnal</label>
                                    <input type="text" name="number" class="form-control" value="{{ old('number', $suggestedNumber) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Tanggal</label>
                                    <input type="date" name="date" class="form-control" value="{{ old('date', $isEdit ? $journal->date->toDateString() : now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Kontak (opsional)</label>
                                    <select name="contact_id" class="form-select">
                                        <option value="">-- Tidak ada --</option>
                                        @foreach ($contacts as $c)
                                            <option value="{{ $c->id }}" {{ old('contact_id', $isEdit ? $journal->contact_id : '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Referensi</label>
                                    <input type="text" name="reference" class="form-control" value="{{ old('reference', $isEdit ? $journal->reference : '') }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <input type="text" name="description" class="form-control" value="{{ old('description', $isEdit ? $journal->description : '') }}">
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="linesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Akun</th>
                                            <th style="width: 30%;">Memo</th>
                                            <th style="width: 17%;">Debit</th>
                                            <th style="width: 17%;">Kredit</th>
                                            <th style="width: 6%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="linesBody">
                                        @foreach ($oldLines as $i => $line)
                                            <tr>
                                                <td>
                                                    <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm acc-select" required>
                                                        <option value="">-- Pilih Akun --</option>
                                                        @foreach ($accounts as $a)
                                                            <option value="{{ $a->id }}" {{ ($line['account_id'] ?? '') == $a->id ? 'selected' : '' }}>{{ $a->code }} - {{ $a->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="text" name="lines[{{ $i }}][memo]" class="form-control form-control-sm" value="{{ $line['memo'] ?? '' }}"></td>
                                                <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control form-control-sm text-end debit-in" value="{{ $line['debit'] ?? '' }}"></td>
                                                <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control form-control-sm text-end credit-in" value="{{ $line['credit'] ?? '' }}"></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-row"><i class="uil uil-trash"></i></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light fw-bold">
                                            <td colspan="2" class="text-end">TOTAL</td>
                                            <td class="text-end" id="totalDebit">0</td>
                                            <td class="text-end" id="totalCredit">0</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="text-end">Selisih (harus 0)</td>
                                            <td colspan="2" class="text-end" id="diff">0</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addRow"><i class="uil uil-plus"></i> Tambah Baris</button>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="saveBtn"><i class="uil uil-save"></i> Simpan Jurnal</button>
                                <a href="{{ url('admin/accounting/journals') }}" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var accountOptions = `@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach`;
    var rowIndex = {{ count($oldLines) }};

    function recalc() {
        var td = 0, tc = 0;
        document.querySelectorAll('.debit-in').forEach(function (el) { td += parseFloat(el.value) || 0; });
        document.querySelectorAll('.credit-in').forEach(function (el) { tc += parseFloat(el.value) || 0; });
        document.getElementById('totalDebit').textContent = td.toLocaleString('id-ID');
        document.getElementById('totalCredit').textContent = tc.toLocaleString('id-ID');
        var diff = (td - tc);
        var diffEl = document.getElementById('diff');
        diffEl.textContent = diff.toLocaleString('id-ID');
        diffEl.className = 'text-end ' + (Math.abs(diff) < 0.005 && td > 0 ? 'text-success fw-bold' : 'text-danger fw-bold');
        document.getElementById('saveBtn').disabled = !(Math.abs(diff) < 0.005 && td > 0);
    }

    function addRow() {
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select name="lines[' + rowIndex + '][account_id]" class="form-select form-select-sm acc-select" required><option value="">-- Pilih Akun --</option>' + accountOptions + '</select></td>' +
            '<td><input type="text" name="lines[' + rowIndex + '][memo]" class="form-control form-control-sm"></td>' +
            '<td><input type="number" step="0.01" min="0" name="lines[' + rowIndex + '][debit]" class="form-control form-control-sm text-end debit-in"></td>' +
            '<td><input type="number" step="0.01" min="0" name="lines[' + rowIndex + '][credit]" class="form-control form-control-sm text-end credit-in"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-row"><i class="uil uil-trash"></i></button></td>';
        document.getElementById('linesBody').appendChild(tr);
        rowIndex++;
    }

    document.getElementById('addRow').addEventListener('click', addRow);

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('debit-in') || e.target.classList.contains('credit-in')) recalc();
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.del-row');
        if (btn) {
            if (document.querySelectorAll('#linesBody tr').length > 2) {
                btn.closest('tr').remove();
                recalc();
            }
        }
    });

    recalc();
})();
</script>
@endsection
