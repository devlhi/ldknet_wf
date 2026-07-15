@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-list-ul me-1"></i> Daftar Akun (Chart of Accounts)</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal"><i class="uil uil-plus"></i> Tambah Akun</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <input type="hidden" name="show_data" value="1">
                            <div class="col-md-3">
                                <select name="type" class="form-select form-select-sm">
                                    <option value="">-- Semua Tipe --</option>
                                    @foreach (['asset' => 'Aset', 'liability' => 'Liabilitas', 'equity' => 'Ekuitas', 'revenue' => 'Pendapatan', 'expense' => 'Beban'] as $k => $v)
                                        <option value="{{ $k }}" {{ $filterType === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari kode / nama akun...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-outline-primary"><i class="uil uil-eye"></i> Tampilkan Data</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Akun</th>
                                        <th>Tipe</th>
                                        <th>Sub</th>
                                        <th class="text-end">Saldo</th>
                                        <th class="text-center">Kas?</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $typeLabels = ['asset' => 'Aset', 'liability' => 'Liabilitas', 'equity' => 'Ekuitas', 'revenue' => 'Pendapatan', 'expense' => 'Beban']; @endphp
                                    @forelse ($accounts as $acc)
                                        @php
                                            $b = $balances->get($acc->id);
                                            $movement = $b ? ((float) $b->total_debit - (float) $b->total_credit) : 0;
                                            $net = (float) $acc->opening_balance + (in_array($acc->type, ['asset', 'expense']) ? $movement : -$movement);
                                        @endphp
                                        <tr>
                                            <td><code>{{ $acc->code }}</code></td>
                                            <td>{{ $acc->name }}</td>
                                            <td><span class="badge bg-light text-dark">{{ $typeLabels[$acc->type] ?? $acc->type }}</span></td>
                                            <td class="small text-muted">{{ $acc->subtype }}</td>
                                            <td class="text-end">Rp {{ number_format($net, 0, ',', '.') }}</td>
                                            <td class="text-center">{!! $acc->is_cash ? '<i class="uil uil-check text-success"></i>' : '' !!}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                                    data-id="{{ $acc->id }}"
                                                    data-code="{{ $acc->code }}"
                                                    data-name="{{ $acc->name }}"
                                                    data-type="{{ $acc->type }}"
                                                    data-subtype="{{ $acc->subtype }}"
                                                    data-cash="{{ $acc->is_cash ? 1 : 0 }}"
                                                    data-active="{{ $acc->is_active ? 1 : 0 }}"
                                                    data-opening="{{ $acc->opening_balance }}"
                                                    data-bs-toggle="modal" data-bs-target="#editAccountModal">
                                                    <i class="uil uil-edit"></i>
                                                </button>
                                                @if (! $acc->is_locked)
                                                    <a href="{{ url('admin/accounting/accounts/delete/'.$acc->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus akun ini?')"><i class="uil uil-trash"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">{{ $showData ? 'Tidak ada akun' : 'Klik Tampilkan Data untuk memuat data.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.accounting.partials.account-modals')
@endsection

@section('scripts')
<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var f = document.getElementById('editAccountForm');
        f.action = "{{ url('admin/accounting/accounts/update') }}/" + this.dataset.id;
        f.querySelector('[name=code]').value = this.dataset.code;
        f.querySelector('[name=name]').value = this.dataset.name;
        f.querySelector('[name=type]').value = this.dataset.type;
        f.querySelector('[name=subtype]').value = this.dataset.subtype || '';
        f.querySelector('[name=opening_balance]').value = this.dataset.opening;
        f.querySelector('[name=is_cash]').checked = this.dataset.cash === '1';
        f.querySelector('[name=is_active]').checked = this.dataset.active === '1';
    });
});
</script>
@endsection
