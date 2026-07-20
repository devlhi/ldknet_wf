@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                @if ($invoice)
                    <a href="{{ url('user/invoice/detail/' . $invoice->code) }}">
                        <div class="card">
                            <div class="card-body">
                                <div>
                                    <h4 class="mb-1 mt-1">Rp <span data-plugin="counterup">{{ number_format($invoice->price) }}</span></h4>
                                    <p class="text-muted mb-0">Tagihan bulan ini</p>
                                </div>
                            </div>
                        </div>
                    </a>
                @else
                    <div class="card">
                        <div class="card-body">
                            <div>
                                <h4 class="mb-1 mt-1"><strong>Belum ada tagihan</strong></h4>
                                <p class="text-muted mb-0">Tagihan bulan ini</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div>
                            <h4 class="mb-1 mt-1">{{ $order->paket ?? '-' }}</h4>
                            <p class="text-muted mb-0">Layanan Anda</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="uil uil-monitor"></i> Status Layanan
                </h5>
            </div>
            <div class="card-body">
                @if ($online)
                    <h5 style="color: green;">
                        Status : <i class="uil uil-check-circle"></i> Online
                    </h5>
                @else
                    <h5 style="color: red;">
                        Status : <i class="uil uil-times-circle"></i> Offline
                    </h5>
                @endif
            </div>
        </div>

        @if ($mode === 'pppoe' && $online && $traffics)
            <div class="card mb-2">
                <div class="card-header">
                    <h5 class="card-title"><i class="uil uil-monitor"></i> Traffic Monitor</h5>
                </div>
                <div class="card-body">
                    <div id="trafficMonitor"></div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
    {{-- jQuery sudah dimuat oleh admin.layout; hanya perlu Highcharts (lokal). --}}
    @if ($mode === 'pppoe' && $online && $traffics)
        <script src="{{ asset('assets/js/highcharts.js') }}"></script>
        <script src="{{ asset('assets/js/highcharts-theme.js') }}"></script>
        <script type="text/javascript">
            var chart;

            function requestData() {
                $.ajax({
                    url: '{{ url('router/traffic/pppoe/' . $traffics) }}',
                    dataType: "json",
                    success: function(data) {
                        var midata = (typeof data === 'string') ? JSON.parse(data) : data;
                        if (Array.isArray(midata) && midata.length > 0) {
                            var TX = parseInt((midata[0] && midata[0].data && midata[0].data[0]) || 0);
                            var RX = parseInt((midata[1] && midata[1].data && midata[1].data[0]) || 0);
                            var x = (new Date()).getTime();
                            var shift = chart.series[0].data.length > 19;
                            chart.series[0].addPoint([x, TX], true, shift);
                            chart.series[1].addPoint([x, RX], true, shift);
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        console.error("Status: " + textStatus + " request: " + XMLHttpRequest);
                        console.error("Error: " + errorThrown);
                    }
                });
            }

            $(document).ready(function() {
                Highcharts.setOptions({
                    global: {
                        useUTC: false
                    }
                });
                chart = new Highcharts.Chart({
                    chart: {
                        renderTo: 'trafficMonitor',
                        animation: Highcharts.svg,
                        type: 'areaspline',
                        events: {
                            load: function() {
                                setInterval(function() {
                                    requestData();
                                }, 8000);
                            }
                        }
                    },
                    title: {
                        text: ''
                    },
                    xAxis: {
                        type: 'datetime',
                        tickPixelInterval: 150,
                        maxZoom: 20 * 1000,
                    },
                    yAxis: {
                        minPadding: 0.2,
                        maxPadding: 0.2,
                        title: {
                            text: null
                        },
                        labels: {
                            formatter: function() {
                                var bytes = this.value;
                                var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
                                if (bytes == 0) return '0 bps';
                                var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                                return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
                            },
                        },
                    },
                    series: [{
                        name: 'Tx',
                        data: [],
                        marker: {
                            symbol: 'circle'
                        }
                    }, {
                        name: 'Rx',
                        data: [],
                        marker: {
                            symbol: 'circle'
                        }
                    }],
                    tooltip: {
                        shared: true
                    },
                });
            });
        </script>
    @endif
@endsection
