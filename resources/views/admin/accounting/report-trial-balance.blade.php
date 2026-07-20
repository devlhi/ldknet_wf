@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-balance-scale me-1"></i> Neraca Saldo</h4>
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-print"></i> Cetak</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small">Per Tanggal</label>
                                <input type="date" name="end" value="{{ $end }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary"><i class="uil uil-filter"></i> Terapkan Filter</button>
                            </div>
                        </form>
<div class="text-center mb-3">
                            <h5 class="mb-0">Neraca Saldo</h5>
                            <small class="text-muted">Per {{ \Carbon\Carbon::parse($end)->format('d F Y') }}</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Akun</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td><code>{{ $row['code'] }}</code></td>
                                            <td>{{ $row['name'] }}</td>
                                            <td class="text-end">{{ $row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '' }}</td>
                                            <td class="text-end">{{ $row['credit'] > 0 ? number_format($row['credit'], 0, ',', '.') : '' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">Tidak ada saldo</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="text-end">TOTAL</td>
                                        <td class="text-end">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            @if (abs($totalDebit - $totalCredit) < 0.005)
                                                <span class="badge bg-success">Seimbang (Balance)</span>
                                            @else
                                                <span class="badge bg-danger">Selisih: Rp {{ number_format(abs($totalDebit - $totalCredit), 0, ',', '.') }}</span>
                                            @endif
                                        </td>
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
