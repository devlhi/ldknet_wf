@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-book-open me-1"></i> Buku Besar</h4>
                        <div class="d-flex gap-2">
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-print"></i> Cetak</button>
                            <a href="{{ url('admin/accounting') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small">Akun</label>
                                <select name="account_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}" {{ $selectedAccount && $selectedAccount->id == $a->id ? 'selected' : '' }}>{{ $a->code }} - {{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Dari</label>
                                <input type="date" name="start" value="{{ $start }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Sampai</label>
                                <input type="date" name="end" value="{{ $end }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary">Tampilkan</button>
                            </div>
                        </form>

                        @if ($ledger && $selectedAccount)
                            <h5>{{ $selectedAccount->code }} - {{ $selectedAccount->name }}</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nomor</th>
                                            <th>Keterangan</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Kredit</th>
                                            <th class="text-end">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="table-light">
                                            <td colspan="5"><strong>Saldo Awal</strong></td>
                                            <td class="text-end"><strong>Rp {{ number_format($ledger['opening'], 0, ',', '.') }}</strong></td>
                                        </tr>
                                        @forelse ($ledger['rows'] as $row)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                                <td>{{ $row['number'] }}</td>
                                                <td class="small">{{ $row['memo'] ?: '-' }}</td>
                                                <td class="text-end">{{ $row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '' }}</td>
                                                <td class="text-end">{{ $row['credit'] > 0 ? number_format($row['credit'], 0, ',', '.') : '' }}</td>
                                                <td class="text-end">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted">Tidak ada transaksi pada periode ini</td></tr>
                                        @endforelse
                                        <tr class="table-light fw-bold">
                                            <td colspan="5" class="text-end">Saldo Akhir</td>
                                            <td class="text-end">Rp {{ number_format($ledger['closing'], 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">Pilih akun untuk menampilkan buku besar.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
