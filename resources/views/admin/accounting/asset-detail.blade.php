@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('admin.accounting.partials.flash')

        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-constructor me-1"></i> {{ $asset->name }}</h4>
                        <div class="d-flex gap-2">
                            @if ($asset->status === 'active' && $asset->book_value > $asset->salvage_value + 0.005)
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#depModal"><i class="uil uil-chart-down"></i> Catat Penyusutan</button>
                            @endif
                            <a href="{{ url('admin/accounting/assets') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th style="width:180px;">Kode</th><td>{{ $asset->code ?: '-' }}</td></tr>
                                    <tr><th>Tanggal Perolehan</th><td>{{ $asset->acquired_date->format('d/m/Y') }}</td></tr>
                                    <tr><th>Harga Perolehan</th><td>Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td></tr>
                                    <tr><th>Nilai Residu</th><td>Rp {{ number_format($asset->salvage_value, 0, ',', '.') }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th style="width:180px;">Umur Manfaat</th><td>{{ $asset->useful_life_months }} bulan</td></tr>
                                    <tr><th>Penyusutan / Bulan</th><td>Rp {{ number_format($asset->monthly_depreciation, 0, ',', '.') }}</td></tr>
                                    <tr><th>Akum. Penyusutan</th><td>Rp {{ number_format($asset->accumulated_depreciation, 0, ',', '.') }}</td></tr>
                                    <tr><th>Nilai Buku</th><td class="fw-bold">Rp {{ number_format($asset->book_value, 0, ',', '.') }}</td></tr>
                                </table>
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2">Riwayat Penyusutan</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr><th>Tanggal</th><th class="text-end">Jumlah</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($asset->depreciations()->orderByDesc('date')->get() as $dep)
                                        <tr>
                                            <td>{{ $dep->date->format('d/m/Y') }}</td>
                                            <td class="text-end">Rp {{ number_format($dep->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-muted">Belum ada penyusutan</td></tr>
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

<div class="modal fade" id="depModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('admin/accounting/assets/depreciate/'.$asset->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Catat Penyusutan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="mb-2">
                        <label class="form-label">Jumlah (kosongkan = otomatis per bulan)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="{{ number_format($asset->monthly_depreciation, 0, ',', '.') }}">
                    </div>
                    <div class="alert alert-info small mb-0">Jurnal otomatis: Debit Beban Penyusutan, Kredit Akumulasi Penyusutan.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
