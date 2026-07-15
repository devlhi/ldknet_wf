@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-invoice me-1"></i> Neraca (Balance Sheet)</h4>
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-print"></i> Cetak</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <input type="hidden" name="show_data" value="1">
                            <div class="col-md-3">
                                <label class="form-label small">Per Tanggal</label>
                                <input type="date" name="end" value="{{ $end }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary"><i class="uil uil-eye"></i> Tampilkan Data</button>
                            </div>
                        </form>

                        @unless ($showData)
                            <div class="alert alert-info">Klik Tampilkan Data untuk memuat laporan.</div>
                        @endunless

                        <div class="text-center mb-3">
                            <h5 class="mb-0">Neraca</h5>
                            <small class="text-muted">Per {{ \Carbon\Carbon::parse($end)->format('d F Y') }}</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light"><tr><th colspan="2">ASET</th></tr></thead>
                                    <tbody>
                                        @forelse ($assets as $a)
                                            <tr><td>{{ $a['name'] }}</td><td class="text-end">Rp {{ number_format($a['amount'], 0, ',', '.') }}</td></tr>
                                        @empty
                                            <tr><td colspan="2" class="text-muted">Tidak ada</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot><tr class="fw-bold table-light"><td class="text-end">TOTAL ASET</td><td class="text-end">Rp {{ number_format($totalAssets, 0, ',', '.') }}</td></tr></tfoot>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light"><tr><th colspan="2">LIABILITAS</th></tr></thead>
                                    <tbody>
                                        @forelse ($liabilities as $l)
                                            <tr><td>{{ $l['name'] }}</td><td class="text-end">Rp {{ number_format($l['amount'], 0, ',', '.') }}</td></tr>
                                        @empty
                                            <tr><td colspan="2" class="text-muted">Tidak ada</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot><tr class="fw-bold"><td class="text-end">Total Liabilitas</td><td class="text-end">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</td></tr></tfoot>
                                    <thead class="table-light"><tr><th colspan="2">EKUITAS</th></tr></thead>
                                    <tbody>
                                        @forelse ($equity as $e)
                                            <tr><td>{{ $e['name'] }}</td><td class="text-end">Rp {{ number_format($e['amount'], 0, ',', '.') }}</td></tr>
                                        @empty
                                            <tr><td colspan="2" class="text-muted">Tidak ada</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold"><td class="text-end">Total Ekuitas</td><td class="text-end">Rp {{ number_format($totalEquity, 0, ',', '.') }}</td></tr>
                                        <tr class="fw-bold table-light"><td class="text-end">TOTAL LIABILITAS + EKUITAS</td><td class="text-end">Rp {{ number_format($totalLiabEquity, 0, ',', '.') }}</td></tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="text-center">
                            @if (abs($totalAssets - $totalLiabEquity) < 0.005)
                                <span class="badge bg-success">Neraca Seimbang</span>
                            @else
                                <span class="badge bg-danger">Selisih: Rp {{ number_format(abs($totalAssets - $totalLiabEquity), 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
