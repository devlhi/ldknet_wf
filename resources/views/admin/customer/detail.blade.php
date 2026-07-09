@extends('admin.layout')

@section('content')
    @foreach ($datacustomer as $row)
        <div class="page-content">
            <div class="container-fluid">

                <div class="row mb-4">
                    <div class="col-xl-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="text-center">

                                    <div class="clearfix"></div>
                                    <div>
                                        <img src="{{ asset('assets/images/users/profile.svg') }}" alt="" class="avatar-lg rounded-circle img-thumbnail">
                                    </div>
                                    <h5 class="mt-3 mb-1">{{ $customer->idpel }}</h5>
                                    <p class="text-muted">Terdaftar sejak {{ tanggal($row->date) }}</p>
                                </div>

                                <hr class="my-4">

                                <div class="text-muted">
                                    <div class="table-responsive mt-4">
                                        <div>
                                            <p class="mb-1">Nama :</p>
                                            <h5 class="font-size-16">{{ $customer->nama }} </h5>
                                        </div>
                                        <div class="mt-4">
                                            <p class="mb-1">Nomor Handphone :</p>
                                            <h5 class="font-size-16">{{ $customer->nomor }}</h5>
                                        </div>
                                        <div class="mt-4">
                                            <p class="mb-1">E-mail :</p>
                                            <h5 class="font-size-16">{{ $customer->email }}</h5>
                                        </div>
                                        <div class="mt-4">
                                            <p class="mb-1">Alamat :</p>
                                            <h5 class="font-size-16">{{ $customer->alamat }}</h5>
                                        </div>

                                        <hr>

                                        <div class="mt-4">
                                            <p class="mb-1">Layanan :</p>
                                            <h5 class="font-size-16">{{ $row->paket }}</h5>
                                        </div>
                                        <div class="mt-4">
                                            <p class="mb-1">Masa Aktif :</p>
                                            <h5 class="font-size-16">{{ tanggal($row->expdate) }}</h5>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card mb-0">
                            <!-- Nav tabs -->
                            <div class="card-header">
                                <h5 class="card-title"><i class="uil uil-history"></i> Riwayat Pembayaran
                                </h5>
                            </div>
                            <div class="card-body">
                                <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nomor Invoice</th>
                                            <th>Periode</th>
                                            <th>Tagihan</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>


                                    <tbody>
                                        @foreach ($invoice as $inv)
                                            @php
                                                if ($inv->status == 'Paid') {
                                                    $label = 'success';
                                                    $statustext = 'Sudah Terbayar';
                                                } elseif ($inv->status == 'Unpaid') {
                                                    $label = 'danger';
                                                    $statustext = 'Belum Terbayar';
                                                } else {
                                                    $label = 'danger';
                                                    $statustext = 'Error';
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $inv->code }}</td>
                                                <td>{{ bulan_indo($inv->expdate) }}</td>
                                                <td>{{ number_format($inv->price) }}</td>

                                                <td><span class="btn btn-sm btn-{{ $label }}">{{ $statustext }}</span></td>
                                                <td>
                                                    @if ($inv->status == 'Unpaid')
                                                        <a href="{{ url('admin/finance/invoice/edit/'.$inv->code) }}" class="btn btn-sm btn-primary"><i class="uil-edit"></i> Update Invoice</a>
                                                    @else
                                                        <a href="{{ url('admin/finance/invoice/detail/'.$inv->code) }}" class="btn btn-sm btn-info"><i class="uil-eye"></i> Detail Invoice</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </div>
                        </div>
                        <br>

                        @php
                            if (!empty($statusppp)) {
                                $css = 'green';
                                $icon = 'uil-check-circle';
                                $text = 'Online';
                            } else {
                                $css = 'red';
                                $icon = 'uil-times-circle';
                                $text = 'Offline';
                            }
                        @endphp
                        <div class="card mb-2">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="uil uil-monitor"></i> Status Layanan
                                </h5>
                            </div>
                            <div class="card-body">
                                <h5 style="color: {{ $css }};">
                                    Status : <i class="uil {{ $icon }}"></i> {{ $text }}
                                </h5>
                            </div>
                        </div>

                        @if ($mode == 'pppoe' && $text == 'Online')
                            <div class="card mb-2">
                                <!-- Nav tabs -->
                                <div class="card-header">

                                    <h5 class="card-title">
                                        <i class="uil uil-monitor"></i> Traffic Monitor
                                    </h5>
                                </div>

                                <div class="card-body">
                                    <script src="{{ asset('assets/js/highcharts.js') }}"></script>
                                    <script src="{{ asset('assets/js/highcharts-theme.js') }}"></script>
                                    <script type="text/javascript">
                                        var chart;
                                        var interface = "{{ $traffics }}";
                                        var n = 3000;

                                        function requestDatta() {
                                            $.ajax({
                                                url: '{{ url('router/traffic/pppoe/'.$traffics) }}',
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
                                            Highcharts.addEvent(Highcharts.Series, 'afterInit', function() {
                                                this.symbolUnicode = {
                                                    circle: '●',
                                                    diamond: '♦',
                                                    square: '■',
                                                    triangle: '▲',
                                                    'triangle-down': '▼'
                                                } [this.symbol] || '●';
                                            });
                                            chart = new Highcharts.Chart({
                                                chart: {
                                                    renderTo: 'trafficMonitor',
                                                    animation: Highcharts.svg,
                                                    type: 'areaspline',
                                                    events: {
                                                        load: function() {
                                                            setInterval(function() {
                                                                requestDatta();
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
                                                    formatter: function() {
                                                        var tdata = ["points", "y", "bps", "kbps", "Mbps", "Gbps", "Tbps", "<span style=\"color:", "color", "series", "; font-size: 1.5em;\">", "symbolUnicode", "</span><b>", "name", ":</b> 0 bps", "push", "log", "floor", ":</b> ", "toFixed", "pow", " ", "each", "", "%d %B %Y %H:%M:%S", "x", "dateFormat", "<br />", " <br/> ", "join"];
                                                        var s = [];
                                                        $[tdata[22]](this[tdata[0]], function(a, b) {
                                                            var c = b[tdata[1]];
                                                            var unit = [tdata[2], tdata[3], tdata[4], tdata[5], tdata[6]];
                                                            if (c == 0) {
                                                                s[tdata[15]](tdata[7] + this[tdata[9]][tdata[8]] + tdata[10] + this[tdata[9]][tdata[11]] + tdata[12] + this[tdata[9]][tdata[13]] + tdata[14])
                                                            };
                                                            var a = parseInt(Math[tdata[17]](Math[tdata[16]](c) / Math[tdata[16]](1024)));
                                                            s[tdata[15]](tdata[7] + this[tdata[9]][tdata[8]] + tdata[10] + this[tdata[9]][tdata[11]] + tdata[12] + this[tdata[9]][tdata[13]] + tdata[18] + parseFloat((c / Math[tdata[20]](1024, a))[tdata[19]](2)) + tdata[21] + unit[a])
                                                        });
                                                        return tdata[23] + Highcharts[tdata[26]](tdata[24], new Date(this[tdata[25]])) + tdata[27] + s[tdata[29]](tdata[28])
                                                    },
                                                    shared: true
                                                },
                                            });
                                        });
                                    </script>

                                    <div id="trafficMonitor"></div>

                                </div>
                            </div>
                        @endif
                    </div>
                    <!-- end row -->

                </div> <!-- container-fluid -->
            </div>

            <!-- End Page-content -->
        </div>
    @endforeach
@endsection
