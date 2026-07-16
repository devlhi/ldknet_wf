@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="uil uil-server me-1"></i> {{ $title }}</h4>
                    <div>
                        <button class="btn btn-primary btn-sm" id="btnRefresh">
                            <i class="uil uil-sync"></i> Refresh
                        </button>
                        <a href="{{ url('admin/nms') }}" class="btn btn-secondary btn-sm">
                            <i class="uil uil-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if (! $showData)
            <div class="alert alert-info d-flex align-items-center justify-content-between">
                <span>Data device belum dimuat.</span>
                <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">Tampilkan Data</a>
            </div>
        @else
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">Info Device</h5>
                    </div>
                    <div class="card-body" id="deviceInfo">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2">Memuat info device...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">Port & SFP Monitor</h5>
                    </div>
                    <div class="card-body" id="portsContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2">Memuat data port & SFP...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="card" id="chartCard" style="display:none;">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">Grafik Historis SFP</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Port</label>
                                <select class="form-control" id="portSelect"></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metric</label>
                                <select class="form-control" id="metricSelect">
                                    <option value="sfp_rx_power">SFP RX Power</option>
                                    <option value="sfp_tx_power">SFP TX Power</option>
                                    <option value="sfp_temperature">SFP Temperature</option>
                                    <option value="sfp_voltage">SFP Voltage</option>
                                    <option value="sfp_tx_bias">SFP TX Bias</option>
                                    <option value="onu_count">ONU Count</option>
                                    <option value="link_status">Link Status</option>
                                </select>
                            </div>
                        </div>
                        <div id="metricChart" class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('assets/libs/apexcharts/apexcharts.css') }}">
@endsection

@section('scripts')
@if ($showData)
<script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
var deviceId = {{ $device->id }};
var pollUrl = '{{ url("admin/nms/device/poll/".$device->id) }}?show_data=1';
var availablePorts = [];

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[char]));
}

function sfpColor(rx) {
    if (rx === null || rx === undefined || rx === '') return 'secondary';
    var val = parseFloat(rx);
    if (isNaN(val)) return 'secondary';
    if (val > -20) return 'success';
    if (val > -25) return 'warning';
    return 'danger';
}

function pollDevice() {
    document.getElementById('deviceInfo').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-2">Memuat info device...</p></div>';
    document.getElementById('portsContainer').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="text-muted mt-2">Memuat data port & SFP...</p></div>';

    fetch(pollUrl)
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                document.getElementById('deviceInfo').innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(res.error) + '</div>';
                document.getElementById('portsContainer').innerHTML = '<p class="text-muted text-center py-4">' + escapeHtml(res.error) + '</p>';
                return;
            }

            var d = res.device || {};
            var infoHtml = '<table class="table table-sm">';
            infoHtml += '<tr><td>Nama</td><td><strong>' + escapeHtml(d.nama) + '</strong></td></tr>';
            infoHtml += '<tr><td>IP</td><td>' + escapeHtml(d.ip) + '</td></tr>';
            infoHtml += '<tr><td>Tipe</td><td>' + escapeHtml(d.tipe) + '</td></tr>';
            if (d.mac_address) infoHtml += '<tr><td>MAC Address</td><td>' + escapeHtml(d.mac_address) + '</td></tr>';
            if (d.work_state) infoHtml += '<tr><td>Work State</td><td>' + escapeHtml(d.work_state) + '</td></tr>';
            if (d.onu_total) infoHtml += '<tr><td>ONU Total</td><td><span class="badge bg-info">' + escapeHtml(d.onu_total) + '</span></td></tr>';
            if (d.uptime) infoHtml += '<tr><td>Uptime</td><td>' + escapeHtml(d.uptime) + '</td></tr>';
            if (d.cpu_load && d.cpu_load !== '-') infoHtml += '<tr><td>CPU Load</td><td>' + escapeHtml(d.cpu_load) + '%</td></tr>';
            if (d.memory_used && d.memory_used !== '-') infoHtml += '<tr><td>Memory Used</td><td>' + escapeHtml(d.memory_used) + '</td></tr>';
            if (d.free_hdd) infoHtml += '<tr><td>Free HDD</td><td>' + escapeHtml(d.free_hdd) + '</td></tr>';
            if (d.routerboard_model) infoHtml += '<tr><td>Model</td><td>' + escapeHtml(d.routerboard_model) + '</td></tr>';
            if (d.serial_number) infoHtml += '<tr><td>Serial</td><td>' + escapeHtml(d.serial_number) + '</td></tr>';
            if (d.ros_version) infoHtml += '<tr><td>RouterOS</td><td>' + escapeHtml(d.ros_version) + '</td></tr>';
            infoHtml += '</table>';
            document.getElementById('deviceInfo').innerHTML = infoHtml;

            var ports = res.ports || [];
            availablePorts = ports.map(p => p.name);
            var portSelect = document.getElementById('portSelect');
            portSelect.innerHTML = ports.map(p => '<option value="' + escapeHtml(p.name) + '">' + escapeHtml(p.name) + '</option>').join('');

            if (ports.length === 0) {
                document.getElementById('portsContainer').innerHTML = '<p class="text-muted text-center py-4">Tidak ada port ditemukan.</p>';
                return;
            }

            var isOlt = d.tipe === 'olt';
            var html = '<div class="table-responsive"><table class="table table-bordered table-sm">';
            if (isOlt) {
                html += '<thead><tr><th>Port</th><th>Tipe</th><th>Link</th><th>RX Power</th><th>TX Power</th><th>Temp</th><th>Voltage</th><th>TxBias</th><th>ONU Online</th><th>SFP Vendor</th><th>SFP SN</th></tr></thead><tbody>';
            } else {
                html += '<thead><tr><th>Port</th><th>Tipe</th><th>Link</th><th>RX Power</th><th>TX Power</th><th>Rate</th><th>SFP Temp</th><th>SFP Vendor</th></tr></thead><tbody>';
            }
            ports.forEach(p => {
                var linkBadge = p.link_status === 'up' ? 'success' : (p.disabled ? 'secondary' : 'danger');
                var rxBadge = sfpColor(p.rx_power);
                var txBadge = sfpColor(p.tx_power);
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(p.name) + '</strong></td>';
                html += '<td>' + escapeHtml(p.type) + '</td>';
                html += '<td><span class="badge bg-' + linkBadge + '">' + escapeHtml(p.link_status) + '</span></td>';
                html += '<td><span class="badge bg-' + rxBadge + '">' + (p.rx_power !== null ? escapeHtml(p.rx_power) + ' dBm' : '-') + '</span></td>';
                html += '<td><span class="badge bg-' + txBadge + '">' + (p.tx_power !== null ? escapeHtml(p.tx_power) + ' dBm' : '-') + '</span></td>';
                if (isOlt) {
                    html += '<td>' + (p.sfp_temperature ? escapeHtml(p.sfp_temperature) : '-') + '</td>';
                    html += '<td>' + (p.sfp_voltage ? escapeHtml(p.sfp_voltage) : '-') + '</td>';
                    html += '<td>' + (p.sfp_tx_bias ? escapeHtml(p.sfp_tx_bias) + ' mA' : '-') + '</td>';
                    html += '<td>' + (p.onu_online !== undefined ? '<span class="badge bg-info">' + p.onu_online + '</span>' : '-') + '</td>';
                    html += '<td>' + escapeHtml(p.sfp_vendor || '-') + '</td>';
                    html += '<td>' + escapeHtml(p.sfp_sn || '-') + '</td>';
                } else {
                    html += '<td>' + escapeHtml(p.rate || '-') + '</td>';
                    html += '<td>' + (p.sfp_temperature ? escapeHtml(p.sfp_temperature) + ' &deg;C' : '-') + '</td>';
                    html += '<td>' + escapeHtml(p.sfp_vendor || '-') + '</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            document.getElementById('portsContainer').innerHTML = html;

            if (availablePorts.length > 0) {
                document.getElementById('chartCard').style.display = 'block';
                loadChart();
            }
        })
        .catch(err => {
            document.getElementById('deviceInfo').innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat info device.</div>';
            document.getElementById('portsContainer').innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat data: ' + escapeHtml(err.message) + '</div>';
        });
}

var chartInstance = null;

function loadChart() {
    var port = document.getElementById('portSelect').value;
    var metric = document.getElementById('metricSelect').value;

    if (!port) return;

    fetch('{{ url("admin/nms/device/metrics") }}/' + deviceId + '/' + encodeURIComponent(port) + '?show_data=1')
        .then(r => r.json())
        .then(res => {
            var filtered = res.data.filter(m => m.metric_type === metric);
            var labels = filtered.map(m => m.recorded_at);
            var values = filtered.map(m => {
                var v = parseFloat(m.value);
                return isNaN(v) ? null : v;
            });

            var unitLabels = {
                'sfp_rx_power': 'dBm',
                'sfp_tx_power': 'dBm',
                'sfp_temperature': 'C',
                'sfp_voltage': 'V',
                'sfp_tx_bias': 'mA',
                'onu_count': 'ONU',
                'link_status': ''
            };
            var unitLabel = unitLabels[metric] || '';

            var chartContainer = document.getElementById('metricChart');
            chartContainer.innerHTML = '';

            if (filtered.length === 0) {
                chartContainer.innerHTML = '<p class="text-muted text-center">Belum ada data historis untuk metric ini.</p>';
                return;
            }

            chartInstance = new ApexCharts(chartContainer, {
                chart: { height: 300, type: 'line', toolbar: { show: false } },
                colors: ['#5b73e8'],
                series: [{ name: metric, data: values }],
                xaxis: { categories: labels, labels: { rotate: -45 } },
                yaxis: { title: { text: unitLabel } },
                tooltip: { y: { formatter: v => unitLabel ? v + ' ' + unitLabel : v } },
                stroke: { curve: 'smooth', width: 2 }
            });
            chartInstance.render();
        })
        .catch(err => {
            document.getElementById('metricChart').innerHTML = '<p class="text-muted text-center">Gagal memuat grafik: ' + escapeHtml(err.message) + '</p>';
        });
}

document.getElementById('btnRefresh').addEventListener('click', pollDevice);
document.getElementById('portSelect').addEventListener('change', loadChart);
document.getElementById('metricSelect').addEventListener('change', loadChart);

pollDevice();
</script>
@endif
@endsection
