@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-users-alt me-1"></i> Kontak</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal"><i class="uil uil-plus"></i> Tambah Kontak</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Semua Tipe --</option>
                                    @foreach (['customer' => 'Pelanggan', 'vendor' => 'Pemasok', 'both' => 'Keduanya', 'employee' => 'Karyawan'] as $k => $v)
                                        <option value="{{ $k }}" {{ $filterType === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nama / email / telp...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-outline-primary">Cari</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Tipe</th>
                                        <th>Email</th>
                                        <th>Telepon</th>
                                        <th class="text-end">Saldo Awal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $typeLabels = ['customer' => 'Pelanggan', 'vendor' => 'Pemasok', 'both' => 'Keduanya', 'employee' => 'Karyawan']; @endphp
                                    @forelse ($contacts as $c)
                                        <tr>
                                            <td>{{ $c->name }}</td>
                                            <td><span class="badge bg-light text-dark">{{ $typeLabels[$c->type] ?? $c->type }}</span></td>
                                            <td>{{ $c->email ?: '-' }}</td>
                                            <td>{{ $c->phone ?: '-' }}</td>
                                            <td class="text-end">Rp {{ number_format($c->opening_balance, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                                    data-id="{{ $c->id }}"
                                                    data-type="{{ $c->type }}"
                                                    data-code="{{ $c->code }}"
                                                    data-name="{{ $c->name }}"
                                                    data-email="{{ $c->email }}"
                                                    data-phone="{{ $c->phone }}"
                                                    data-tax="{{ $c->tax_number }}"
                                                    data-address="{{ $c->address }}"
                                                    data-opening="{{ $c->opening_balance }}"
                                                    data-active="{{ $c->is_active ? 1 : 0 }}"
                                                    data-bs-toggle="modal" data-bs-target="#editContactModal">
                                                    <i class="uil uil-edit"></i>
                                                </button>
                                                <a href="{{ url('admin/accounting/contacts/delete/'.$c->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kontak ini?')"><i class="uil uil-trash"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada kontak</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $contacts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.accounting.partials.contact-modals')
@endsection

@section('scripts')
<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var f = document.getElementById('editContactForm');
        f.action = "{{ url('admin/accounting/contacts/update') }}/" + this.dataset.id;
        f.querySelector('[name=type]').value = this.dataset.type;
        f.querySelector('[name=code]').value = this.dataset.code || '';
        f.querySelector('[name=name]').value = this.dataset.name;
        f.querySelector('[name=email]').value = this.dataset.email || '';
        f.querySelector('[name=phone]').value = this.dataset.phone || '';
        f.querySelector('[name=tax_number]').value = this.dataset.tax || '';
        f.querySelector('[name=address]').value = this.dataset.address || '';
        f.querySelector('[name=opening_balance]').value = this.dataset.opening;
        f.querySelector('[name=is_active]').checked = this.dataset.active === '1';
    });
});
</script>
@endsection
