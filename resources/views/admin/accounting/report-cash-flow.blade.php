@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-money-withdrawal me-1"></i> Laporan Arus Kas</h4>
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-print"></i> Cetak</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small">Dari</label>
                                <input type="date" name="start" value="{{ $start }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Sampai</label>
                                <input type="date" name="end" value="{{ $end }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary"><i class="uil uil-filter"></i> Terapkan Filter</button>
                            </div>
                        </form>
<div class="row mb-3">
                            <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Saldo Awal</small><div class="fw-bold">Rp {{ number_format($opening, 0, ',', '.') }}</div></div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Kas Masuk</small><div class="fw-bold text-success">Rp {{ number_format($inflow, 0, ',', '.') }}</div></div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Kas Keluar</small><div class="fw-bold text-danger">Rp {{ number_format($outflow, 0, ',', '.') }}</div></div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Saldo Akhir</small><div class="fw-bold">Rp {{ number_format($closing, 0, ',', '.') }}</div></div></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nomor</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Masuk</th>
                                        <th class="text-end">Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                            <td>{{ $row['number'] }}</td>
                                            <td class="small">{{ $row['memo'] ?: '-' }}</td>
                                            <td class="text-end">{{ $row['in'] > 0 ? number_format($row['in'], 0, ',', '.') : '' }}</td>
                                            <td class="text-end">{{ $row['out'] > 0 ? number_format($row['out'], 0, ',', '.') : '' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">Tidak ada mutasi kas pada periode ini</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="3" class="text-end">TOTAL</td>
                                        <td class="text-end">Rp {{ number_format($inflow, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($outflow, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
