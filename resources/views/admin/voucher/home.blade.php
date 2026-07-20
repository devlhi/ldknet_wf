@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @if (session('auth_errors'))
            <div class="alert alert-warning" role="alert">
                @foreach ((array) session('auth_errors') as $err)
                    {{ $err }}
                @endforeach
            </div>
        @endif
        <div class="row">
            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div>
                            <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $vcrtoday }}</span> Voucher ( Rp <span data-plugin="counterup">{{ number_format($today) }}</span> )</h4>
                            <p class="text-muted mb-0">Pendapatan voucher hari ini</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div>
                            <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $vcrystrdy }}</span> Voucher ( Rp <span data-plugin="counterup">{{ number_format($yesterday) }}</span> )</h4>
                            <p class="text-muted mb-0">Pendapatan voucher Kemarin</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div>
                            <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $vcrmonth }}</span> Voucher ( Rp <span data-plugin="counterup">{{ number_format($month) }}</span> )</h4>
                            <p class="text-muted mb-0">Pendapatan voucher bulan ini</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"> <i class="uil-chart-growth"></i> Laporan</h3>
                    </div>
                    <div class="card-body">
                        <div id="container" style="height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/highcharts.js') }}"></script>
<script src="{{ asset('assets/js/highcharts-theme.js') }}"></script>
<script>
    $(document).ready(function() {
        var reportByDay = @json($reportByDay);
        var daysInMonth = {{ $daysInMonth }};

        var dates = [];
        var total_harga = [];
        var total_voucher = [];

        var year = {{ date('Y') }};
        var month = {{ date('m') }};

        for (var i = 1; i <= daysInMonth; i++) {
            var date = new Date(year, month - 1, i);
            var dateString = date.getDate() + ' ' + date.toLocaleString('id-ID', {
                month: 'long'
            });

            dates.push(dateString);

            var dataForDay = reportByDay.find(item => new Date(item.date).getDate() === i);

            if (dataForDay) {
                total_harga.push(parseInt(dataForDay.total_harga));
                total_voucher.push(dataForDay.total_voucher + ' Voucher');
            } else {
                total_harga.push(0);
                total_voucher.push('0 Voucher');
            }
        }

        Highcharts.chart('container', {
            chart: {
                type: 'area',
            },
            title: {
                text: 'Grafik Laporan'
            },
            xAxis: {
                categories: dates
            },
            yAxis: {
                title: {
                    text: 'Total Penjualan'
                }
            },
            legend: {
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom'
            },
            tooltip: {
                formatter: function() {
                    var dateString = dates[this.point.x];
                    var parts = dateString.split(' ');
                    var tanggal = parts[0];
                    var bulan = parts[1];

                    return 'Tanggal: <b>' + tanggal + ' ' + bulan + '</b><br>' +
                        'Total Penjualan: <b>Rp ' + this.y.toLocaleString() + '</b><br>' +
                        'Total Voucher: <b>' + total_voucher[this.point.index] + '</b>';
                }
            },
            series: [{
                name: 'Total Penjualan',
                data: total_harga
            }]
        });
    });
</script>
@endsection
