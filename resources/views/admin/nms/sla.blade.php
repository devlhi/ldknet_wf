@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        @foreach (session('success') as $suc)
                            {{ $suc }}<br>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-file-chart-alt me-1"></i> SLA Report - {{ $periodLabel }}</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ url('admin/nms/sla/settings/global') }}" class="btn btn-sm btn-info text-white"><i class="uil uil-globe"></i> Pengaturan Global</a>
                            <a href="{{ url('admin/nms/sla?period=today') }}" class="btn btn-sm {{ $period === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">Hari Ini</a>
                            <a href="{{ url('admin/nms/sla?period=month') }}" class="btn btn-sm {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">Bulan Ini</a>
                            <a href="{{ url('admin/nms/sla?period=year') }}" class="btn btn-sm {{ $period === 'year' ? 'btn-primary' : 'btn-outline-primary' }}">Tahun Ini</a>
                            <button onclick="window.print()" class="btn btn-sm btn-danger"><i class="uil uil-file-download"></i> Export PDF</button>
                            <a href="{{ url('admin/nms') }}" class="btn btn-sm btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="sla-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Device</th>
                                        <th>IP</th>
                                        <th>Tipe</th>
                                        <th class="text-center">Metode Cek</th>
                                        <th class="text-center">Interface</th>
                                        <th class="text-center">UP</th>
                                        <th class="text-center">DOWN</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">SLA (%)</th>
                                        <th class="text-center">Target</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($slaData as $i => $row)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $row['nama'] }}</td>
                                            <td>{{ $row['ip'] }}</td>
                                            <td>{{ $row['tipe'] }}</td>
                                            <td class="text-center">
                                                @if ($row['check_type'] === 'ping')
                                                    <span class="badge bg-info">Ping (IP)</span>
                                                @else
                                                    <span class="badge bg-primary">Interface</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $row['interface'] ?: '-' }}</td>
                                            <td class="text-center text-success fw-bold">{{ $row['up_count'] }}</td>
                                            <td class="text-center text-danger fw-bold">{{ $row['down_count'] }}</td>
                                            <td class="text-center">{{ $row['total_checks'] }}</td>
                                            <td class="text-center fw-bold" style="font-size: 14px;">
                                                {{ $row['sla'] }}%
                                            </td>
                                            <td class="text-center">{{ $row['target_sla'] }}%</td>
                                            <td class="text-center">
                                                @if ($row['meets_target'])
                                                    <span class="badge bg-success">OK</span>
                                                @else
                                                    <span class="badge bg-danger">SLA MISS</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ url('admin/nms/sla/settings/'.$row['id']) }}" class="btn btn-sm btn-warning text-white">
                                                    <i class="uil uil-setting"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="13" class="text-center text-muted py-4">Tidak ada data SLA untuk periode ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold" style="background: #f8f9fa;">
                                        <td colspan="9" class="text-end">Rata-rata SLA:</td>
                                        <td class="text-center" style="font-size: 14px;">
                                            @php
                                                $avgSla = $slaData->count() > 0 ? round($slaData->avg('sla'), 2) : 0;
                                            @endphp
                                            {{ $avgSla }}%
                                        </td>
                                        <td colspan="3"></td>
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

@section('css')
<style>
@media print {
    .navbar, .sidebar, .vertical-menu, .main-content .navbar-header,
    .btn, .card-header .d-flex > div, .footer, .right-bar, .button-items {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .page-content {
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        border: none !important;
    }
    .card-header h4::after {
        content: " - {{ $periodLabel }}";
    }
    body {
        background: white !important;
    }
    #sla-table {
        width: 100% !important;
        font-size: 12px;
    }
    #sla-table th {
        background: #333 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    #sla-table .bg-success, #sla-table .bg-warning, #sla-table .bg-danger, #sla-table .bg-info {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
@endsection

@section('scripts')
<script>
// Auto-set document title for PDF filename
document.addEventListener('DOMContentLoaded', function() {
    var originalTitle = document.title;
    window.addEventListener('beforeprint', function() {
        document.title = 'SLA_Report_{{ str_replace(' ', '_', $periodLabel) }}';
    });
    window.addEventListener('afterprint', function() {
        document.title = originalTitle;
    });
});
</script>
@endsection
