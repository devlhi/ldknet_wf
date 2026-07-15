@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-9 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-chart-line me-1"></i> Laporan Laba Rugi</h4>
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-print"></i> Cetak</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <input type="hidden" name="show_data" value="1">
                            <div class="col-md-3">
                                <label class="form-label small">Dari</label>
                                <input type="date" name="start" value="{{ $start }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Sampai</label>
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
                            <h5 class="mb-0">Laporan Laba Rugi</h5>
                            <small class="text-muted">Periode {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}</small>
                        </div>

                        <table class="table table-sm">
                            <tr class="table-light"><th colspan="2">PENDAPATAN</th></tr>
                            @forelse ($revenue as $r)
                                <tr><td class="ps-4">{{ $r['name'] }}</td><td class="text-end">Rp {{ number_format($r['amount'], 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td class="ps-4 text-muted" colspan="2">Tidak ada pendapatan</td></tr>
                            @endforelse
                            <tr class="fw-bold"><td class="text-end">Total Pendapatan</td><td class="text-end">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td></tr>

                            <tr class="table-light"><th colspan="2">HARGA POKOK PENJUALAN (HPP)</th></tr>
                            @forelse ($cogs as $r)
                                <tr><td class="ps-4">{{ $r['name'] }}</td><td class="text-end">Rp {{ number_format($r['amount'], 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td class="ps-4 text-muted" colspan="2">Tidak ada HPP</td></tr>
                            @endforelse
                            <tr class="fw-bold"><td class="text-end">Total HPP</td><td class="text-end">Rp {{ number_format($totalCogs, 0, ',', '.') }}</td></tr>

                            <tr class="fw-bold table-info"><td class="text-end">LABA KOTOR</td><td class="text-end">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td></tr>

                            <tr class="table-light"><th colspan="2">BEBAN OPERASIONAL</th></tr>
                            @forelse ($expense as $r)
                                <tr><td class="ps-4">{{ $r['name'] }}</td><td class="text-end">Rp {{ number_format($r['amount'], 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td class="ps-4 text-muted" colspan="2">Tidak ada beban</td></tr>
                            @endforelse
                            <tr class="fw-bold"><td class="text-end">Total Beban</td><td class="text-end">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td></tr>

                            <tr class="fw-bold {{ $netProfit >= 0 ? 'table-success' : 'table-danger' }}">
                                <td class="text-end">LABA (RUGI) BERSIH</td>
                                <td class="text-end">Rp {{ number_format($netProfit, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
