@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0"><i class="uil uil-calculator-alt me-1"></i> Dashboard Akuntansi</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ url('admin/accounting/journals/create') }}" class="btn btn-sm btn-primary"><i class="uil uil-plus"></i> Jurnal Baru</a>
                    </div>
                </div>
            </div>
        </div>

        @unless ($showData)
            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span>Data ikhtisar keuangan dan jurnal belum dimuat.</span>
                <a href="{{ request()->fullUrlWithQuery(['show_data' => 1]) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
            </div>
        @endunless

        @if ($showData)
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Saldo Kas & Bank</p>
                        <h4 class="mb-0">Rp {{ number_format($cash, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Pendapatan (Bulan Ini)</p>
                        <h4 class="mb-0 text-success">Rp {{ number_format($income, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Beban (Bulan Ini)</p>
                        <h4 class="mb-0 text-danger">Rp {{ number_format($expense, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Laba Bersih (Bulan Ini)</p>
                        <h4 class="mb-0 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($netProfit, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Menu Cepat</h5>
                        <div class="list-group">
                            <a href="{{ url('admin/accounting/accounts') }}" class="list-group-item list-group-item-action"><i class="uil uil-list-ul me-1"></i> Daftar Akun ({{ $accountCount }})</a>
                            <a href="{{ url('admin/accounting/journals') }}" class="list-group-item list-group-item-action"><i class="uil uil-book me-1"></i> Jurnal Umum ({{ $journalCount }})</a>
                            <a href="{{ url('admin/accounting/contacts') }}" class="list-group-item list-group-item-action"><i class="uil uil-users-alt me-1"></i> Kontak ({{ $contactCount }})</a>
                            <a href="{{ url('admin/accounting/reports/trial-balance') }}" class="list-group-item list-group-item-action"><i class="uil uil-balance-scale me-1"></i> Neraca Saldo</a>
                            <a href="{{ url('admin/accounting/reports/profit-loss') }}" class="list-group-item list-group-item-action"><i class="uil uil-chart-line me-1"></i> Laba Rugi</a>
                            <a href="{{ url('admin/accounting/reports/balance-sheet') }}" class="list-group-item list-group-item-action"><i class="uil uil-invoice me-1"></i> Neraca</a>
                            <a href="{{ url('admin/accounting/reports/cash-flow') }}" class="list-group-item list-group-item-action"><i class="uil uil-money-withdrawal me-1"></i> Arus Kas</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">Jurnal Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nomor</th>
                                        <th>Deskripsi</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentJournals as $j)
                                        <tr>
                                            <td>{{ $j->date->format('d/m/Y') }}</td>
                                            <td><a href="{{ url('admin/accounting/journals/detail/'.$j->id) }}">{{ $j->number }}</a></td>
                                            <td>{{ $j->description ?: '-' }}</td>
                                            <td class="text-end">Rp {{ number_format($j->total, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">Belum ada jurnal</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
