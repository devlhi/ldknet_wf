@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-bill me-1"></i> Biaya / Pengeluaran</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal"><i class="uil uil-plus"></i> Catat Biaya</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-2"><input type="date" name="start" value="{{ $start }}" class="form-control form-control-sm"></div>
                            <div class="col-md-2"><input type="date" name="end" value="{{ $end }}" class="form-control form-control-sm"></div>
                            <div class="col-md-4"><input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nomor / deskripsi..."></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nomor</th><th>Tanggal</th><th>Deskripsi</th><th>Akun Beban</th><th>Dibayar Dari</th><th class="text-end">Jumlah</th><th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($expenses as $exp)
                                        <tr>
                                            <td>{{ $exp->number }}</td>
                                            <td>{{ $exp->date->format('d/m/Y') }}</td>
                                            <td>{{ $exp->description ?: '-' }}</td>
                                            <td class="small">{{ optional($expenseAccounts->firstWhere('id', $exp->expense_account_id))->name ?? '-' }}</td>
                                            <td class="small">{{ optional($cashAccounts->firstWhere('id', $exp->payment_account_id))->name ?? '-' }}</td>
                                            <td class="text-end">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                                    data-id="{{ $exp->id }}"
                                                    data-date="{{ $exp->date->toDateString() }}"
                                                    data-contact="{{ $exp->contact_id }}"
                                                    data-expacc="{{ $exp->expense_account_id }}"
                                                    data-payacc="{{ $exp->payment_account_id }}"
                                                    data-ref="{{ $exp->reference }}"
                                                    data-desc="{{ $exp->description }}"
                                                    data-amount="{{ $exp->amount }}"
                                                    data-bs-toggle="modal" data-bs-target="#editExpenseModal">
                                                    <i class="uil uil-edit"></i>
                                                </button>
                                                <a href="{{ url('admin/accounting/expenses/delete/'.$exp->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus biaya ini?')"><i class="uil uil-trash"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">Belum ada biaya</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $expenses->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.accounting.partials.expense-modals')
@endsection

@section('scripts')
<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var f = document.getElementById('editExpenseForm');
        f.action = "{{ url('admin/accounting/expenses/update') }}/" + this.dataset.id;
        f.querySelector('[name=date]').value = this.dataset.date;
        f.querySelector('[name=contact_id]').value = this.dataset.contact || '';
        f.querySelector('[name=expense_account_id]').value = this.dataset.expacc;
        f.querySelector('[name=payment_account_id]').value = this.dataset.payacc;
        f.querySelector('[name=reference]').value = this.dataset.ref || '';
        f.querySelector('[name=description]').value = this.dataset.desc || '';
        f.querySelector('[name=amount]').value = this.dataset.amount;
    });
});
</script>
@endsection
