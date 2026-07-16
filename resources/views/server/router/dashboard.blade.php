@extends('server.router.layout')

@section('content')
<!-- ============================================================== -->
<!-- Start right Content here -->
<!-- ============================================================== -->

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Dashboard Router</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        @if (! ($dataLoaded ?? true))
            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="mdi mdi-information-outline me-2"></i> Data dashboard router belum dimuat. Klik tombol untuk mengambil data dari Mikrotik.</span>
                <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-download"></i> Tampilkan Data
                </a>
            </div>
        @else

        <div class="row">
            <div class="col-xl-4 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="avatar-sm me-4">
                                <img src="{{ asset('assets/images/winbox.png') }}" height="50px" width="50px">
                            </div>

                            <div class="flex-grow-1 align-self-center">
                                <div class="border-bottom pb-1">
                                    <h5 class="text-truncate font-size-16 mb-1"><a href="#" class="text-dark">{{ $nameserver }}</a></h5>
                                    <p class="text-muted">
                                        <i class="uil-server"></i> {{ $dnsserver }}
                                    </p>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mt-3">
                                            <p class="text-muted mb-2">Model : {{ $model }} </p>
                                            <p class="text-muted mb-2">Uptime : {{ formatDTM($uptime) }}</p>
                                            <p class="text-muted mb-2">Timezone : {{ $timezone }} </p>
                                            <p class="text-muted mb-2">Architecture : {{ $architecture }}</p>
                                            <p class="text-muted mb-2">Routers OS : {{ $version }} </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-xl-8 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title"> <i class="uil-notes"></i> Hotspot Log</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <tbody>
                                    @for ($i = 0; $i < $totalhotspotlog && $i < 4; $i++)
                                        <tr>
                                            <td>{{ $hotspotlog[$i]['time'] ?? '' }}</td>
                                            <td>{{ $hotspotlog[$i]['message'] ?? '' }}</td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card traffic-card">
                <div class="card-header traffic-card__header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="traffic-card__icon"><i class="uil-chart-line"></i></span>
                        <div>
                            <h4 class="card-title mb-0">Traffic Monitor</h4>
                            <small class="traffic-card__live" id="trafficLive">
                                <span class="traffic-dot"></span> Live &middot; update tiap 8 detik
                            </small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 text-muted small">Interface</label>
                        <select id="interfaceSelect" class="form-select form-select-sm traffic-card__select">
                            <option value="">Pilih Interface</option>
                            @foreach ($interface as $iface)
                                <option value="{{ $iface['name'] ?? '' }}" {{ ($iface['name'] ?? '') === $traffics ? 'selected' : '' }}>{{ $iface['name'] ?? '(unnamed)' }}</option>
                            @endforeach
                            @if (empty($interface) && ! empty($traffics))
                                <option value="{{ $traffics }}" selected>{{ $traffics }}</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="traffic-stat traffic-stat--tx">
                                <div class="traffic-stat__label"><i class="uil-arrow-up"></i> Download (Tx)</div>
                                <div class="traffic-stat__value" id="statTx">0 bps</div>
                                <div class="traffic-stat__peak">Peak: <span id="peakTx">0 bps</span></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="traffic-stat traffic-stat--rx">
                                <div class="traffic-stat__label"><i class="uil-arrow-down"></i> Upload (Rx)</div>
                                <div class="traffic-stat__value" id="statRx">0 bps</div>
                                <div class="traffic-stat__peak">Peak: <span id="peakRx">0 bps</span></div>
                            </div>
                        </div>
                    </div>
                    <div id="trafficMonitor" style="height: 320px; width: 100%;"></div>
                    <div id="trafficMonitorError" class="alert alert-warning d-none mt-2 mb-0"></div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('css')
<style>
    .traffic-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .traffic-card__header {
        background: linear-gradient(135deg, #0f172a 0%, #1e40af 52%, #0284c7 100%);
        border-bottom: 0;
        color: #fff;
        padding: 18px 22px;
    }

    .traffic-card__header .card-title,
    .traffic-card__header .form-label,
    .traffic-card__header .text-muted {
        color: #fff !important;
    }

    .traffic-card__icon {
        align-items: center;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 14px;
        display: inline-flex;
        font-size: 24px;
        height: 46px;
        justify-content: center;
        width: 46px;
    }

    .traffic-card__live {
        color: rgba(255, 255, 255, 0.78);
    }

    .traffic-card__select {
        border: 0;
        border-radius: 999px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        min-width: 220px;
    }

    .traffic-dot {
        animation: trafficPulse 1.4s ease-in-out infinite;
        background: #22c55e;
        border-radius: 999px;
        box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.16);
        display: inline-block;
        height: 8px;
        margin-right: 6px;
        width: 8px;
    }

    .traffic-card.is-error .traffic-dot {
        animation: none;
        background: #f59e0b;
        box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.16);
    }

    .traffic-stat {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px 18px;
        position: relative;
        overflow: hidden;
    }

    .traffic-stat::before {
        border-radius: 999px;
        content: '';
        height: 92px;
        opacity: 0.12;
        position: absolute;
        right: -28px;
        top: -38px;
        width: 92px;
    }

    .traffic-stat--tx::before {
        background: #0ea5e9;
    }

    .traffic-stat--rx::before {
        background: #f43f5e;
    }

    .traffic-stat__label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .traffic-stat__value {
        color: #0f172a;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.15;
        margin-top: 6px;
    }

    .traffic-stat__peak {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 4px;
    }

    @keyframes trafficPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .55; transform: scale(.78); }
    }
</style>
@endsection

@section('scripts')
@if ($dataLoaded ?? true)
    <script src="{{ asset('assets/js/highcharts.js') }}"></script>
    <script src="{{ asset('assets/js/highcharts-theme.js') }}"></script>
    <script type="text/javascript">
        (function() {
            var chart;
            var trafficInterval;
            var selectedInterface = @json($traffics);
            var trafficUrl = @json(url('router/traffic/fix'));
            var csrfToken = @json(csrf_token());
            var peakTx = 0;
            var peakRx = 0;

            var COLOR_TX = '#0ea5e9';
            var COLOR_RX = '#f43f5e';

            function showTrafficError(message) {
                $('#trafficMonitorError').removeClass('d-none').text(message);
                $('.traffic-card').addClass('is-error');
                $('#trafficLive').html('<span class="traffic-dot"></span> Terputus');
            }

            function clearTrafficError() {
                $('#trafficMonitorError').addClass('d-none').text('');
                $('.traffic-card').removeClass('is-error');
                $('#trafficLive').html('<span class="traffic-dot"></span> Live &middot; update tiap 8 detik');
            }

            function formatBits(bytes) {
                bytes = parseInt(bytes || 0);
                var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
                if (bytes === 0) return '0 bps';
                var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function resetStats() {
                peakTx = 0;
                peakRx = 0;
                $('#statTx').text('0 bps');
                $('#statRx').text('0 bps');
                $('#peakTx').text('0 bps');
                $('#peakRx').text('0 bps');
            }

            function updateStats(tx, rx) {
                $('#statTx').text(formatBits(tx));
                $('#statRx').text(formatBits(rx));
                if (tx > peakTx) { peakTx = tx; $('#peakTx').text(formatBits(tx)); }
                if (rx > peakRx) { peakRx = rx; $('#peakRx').text(formatBits(rx)); }
            }

            function gradient(color) {
                return {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, Highcharts.color(color).setOpacity(0.35).get('rgba')],
                        [1, Highcharts.color(color).setOpacity(0).get('rgba')]
                    ]
                };
            }

            function requestDatta() {
                if (!chart) {
                    return;
                }

                if (!selectedInterface) {
                    showTrafficError('Pilih interface terlebih dahulu untuk menampilkan grafik traffic.');
                    return;
                }

                $.ajax({
                    url: trafficUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        interface: selectedInterface,
                        show_data: 1,
                        _token: csrfToken
                    },
                    success: function(data) {
                        clearTrafficError();
                        var midata = (typeof data === 'string') ? JSON.parse(data) : data;
                        var tx = parseInt((midata[0] && midata[0].data && midata[0].data[0]) || 0);
                        var rx = parseInt((midata[1] && midata[1].data && midata[1].data[0]) || 0);
                        var x = (new Date()).getTime();
                        var shift = chart.series[0].data.length > 19;
                        chart.series[0].addPoint([x, tx], true, shift);
                        chart.series[1].addPoint([x, rx], true, shift);
                        updateStats(tx, rx);
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Grafik traffic belum bisa mengambil data dari router. Cek koneksi Mikrotik atau pilih interface lain.';

                        showTrafficError(message);
                    }
                });
            }

            $(function() {
                if (typeof Highcharts === 'undefined') {
                    showTrafficError('Highcharts tidak berhasil dimuat.');
                    return;
                }

                Highcharts.setOptions({
                    global: {
                        useUTC: false
                    }
                });

                chart = Highcharts.chart('trafficMonitor', {
                    chart: {
                        animation: Highcharts.svg,
                        type: 'areaspline',
                        backgroundColor: 'transparent',
                        spacing: [12, 8, 8, 4],
                        style: {
                            fontFamily: 'inherit'
                        }
                    },
                    title: { text: null },
                    legend: {
                        align: 'right',
                        verticalAlign: 'top',
                        symbolRadius: 6,
                        itemStyle: { color: '#475569', fontWeight: '600' }
                    },
                    credits: { enabled: false },
                    xAxis: {
                        type: 'datetime',
                        tickPixelInterval: 150,
                        maxZoom: 20 * 1000,
                        lineColor: '#e2e8f0',
                        tickColor: '#e2e8f0',
                        gridLineColor: 'transparent',
                        labels: { style: { color: '#94a3b8' } }
                    },
                    yAxis: {
                        minPadding: 0.2,
                        maxPadding: 0.2,
                        gridLineColor: '#eef2f7',
                        gridLineDashStyle: 'Dash',
                        title: { text: null },
                        labels: {
                            style: { color: '#94a3b8' },
                            formatter: function() {
                                return formatBits(this.value);
                            },
                        },
                    },
                    plotOptions: {
                        areaspline: {
                            lineWidth: 2.5,
                            marker: {
                                enabled: false,
                                radius: 3,
                                states: { hover: { enabled: true, radiusPlus: 2 } }
                            },
                            states: { hover: { lineWidth: 3 } }
                        }
                    },
                    series: [{
                        name: 'Tx (Download)',
                        color: COLOR_TX,
                        fillColor: gradient(COLOR_TX),
                        data: []
                    }, {
                        name: 'Rx (Upload)',
                        color: COLOR_RX,
                        fillColor: gradient(COLOR_RX),
                        data: []
                    }],
                    tooltip: {
                        useHTML: true,
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        borderWidth: 0,
                        borderRadius: 10,
                        shadow: false,
                        style: { color: '#fff' },
                        formatter: function() {
                            var rows = [];
                            $.each(this.points, function() {
                                rows.push('<span style="color:' + this.series.color + '">●</span> ' + this.series.name + ': <b>' + formatBits(this.y) + '</b>');
                            });

                            return '<span style="font-size:11px;opacity:.75">' + Highcharts.dateFormat('%H:%M:%S', new Date(this.x)) + '</span><br />' + rows.join('<br />');
                        },
                        shared: true
                    },
                });

                if (!selectedInterface) {
                    showTrafficError('Pilih interface terlebih dahulu untuk menampilkan grafik traffic.');
                } else {
                    requestDatta();
                }

                trafficInterval = setInterval(requestDatta, 8000);

                $('#interfaceSelect').on('change', function() {
                    selectedInterface = $(this).val();
                    clearTrafficError();
                    chart.series[0].setData([]);
                    chart.series[1].setData([]);
                    resetStats();

                    if (!selectedInterface) {
                        showTrafficError('Pilih interface terlebih dahulu untuk menampilkan grafik traffic.');
                        return;
                    }

                    requestDatta();
                });

                $(window).on('beforeunload', function() {
                    clearInterval(trafficInterval);
                });
            });
        })();
    </script>
@endif
@endsection
