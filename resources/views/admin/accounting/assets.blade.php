@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-constructor me-1"></i> Aset Tetap</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal"><i class="uil uil-plus"></i> Tambah Aset</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-4"><input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nama aset..."></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary">Cari</button></div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th><th>Tgl Perolehan</th><th class="text-end">Harga Perolehan</th>
                                        <th class="text-end">Akum. Penyusutan</th><th class="text-end">Nilai Buku</th>
                                        <th class="text-center">Status</th><th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assets as $asset)
                                        <tr>
                                            <td><a href="{{ url('admin/accounting/assets/detail/'.$asset->id) }}">{{ $asset->name }}</a></td>
                                            <td>{{ $asset->acquired_date->format('d/m/Y') }}</td>
                                            <td class="text-end">Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($asset->accumulated_depreciation, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($asset->book_value, 0, ',', '.') }}</td>
                                            <td class="text-center"><span class="badge {{ $asset->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ strtoupper($asset->status) }}</span></td>
                                            <td class="text-center">
                                                <a href="{{ url('admin/accounting/assets/detail/'.$asset->id) }}" class="btn btn-sm btn-outline-info"><i class="uil uil-eye"></i></a>
                                                <button class="btn btn-sm btn-outline-primary edit-btn"
                                                    data-id="{{ $asset->id }}"
                                                    data-code="{{ $asset->code }}"
                                                    data-name="{{ $asset->name }}"
                                                    data-date="{{ $asset->acquired_date->toDateString() }}"
                                                    data-cost="{{ $asset->acquisition_cost }}"
                                                    data-salvage="{{ $asset->salvage_value }}"
                                                    data-life="{{ $asset->useful_life_months }}"
                                                    data-assetacc="{{ $asset->asset_account_id }}"
                                                    data-accumacc="{{ $asset->accum_account_id }}"
                                                    data-expacc="{{ $asset->expense_account_id }}"
                                                    data-notes="{{ $asset->notes }}"
                                                    data-bs-toggle="modal" data-bs-target="#editAssetModal">
                                                    <i class="uil uil-edit"></i>
                                                </button>
                                                <a href="{{ url('admin/accounting/assets/delete/'.$asset->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus aset ini?')"><i class="uil uil-trash"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">Belum ada aset</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $assets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.accounting.partials.asset-modals')
@endsection

@section('scripts')
<script>
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var f = document.getElementById('editAssetForm');
        f.action = "{{ url('admin/accounting/assets/update') }}/" + this.dataset.id;
        f.querySelector('[name=code]').value = this.dataset.code || '';
        f.querySelector('[name=name]').value = this.dataset.name;
        f.querySelector('[name=acquired_date]').value = this.dataset.date;
        f.querySelector('[name=acquisition_cost]').value = this.dataset.cost;
        f.querySelector('[name=salvage_value]').value = this.dataset.salvage;
        f.querySelector('[name=useful_life_months]').value = this.dataset.life;
        f.querySelector('[name=asset_account_id]').value = this.dataset.assetacc || '';
        f.querySelector('[name=accum_account_id]').value = this.dataset.accumacc || '';
        f.querySelector('[name=expense_account_id]').value = this.dataset.expacc || '';
        f.querySelector('[name=notes]').value = this.dataset.notes || '';
    });
});
</script>
@endsection
