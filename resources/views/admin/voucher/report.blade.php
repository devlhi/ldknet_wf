@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Laporan</h4>
                </div>
            </div>
        </div>

        @if (session('auth_errors'))
            <div class="alert alert-warning" role="alert">
                @foreach ((array) session('auth_errors') as $err)
                    {{ $err }}
                @endforeach
            </div>
        @endif

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <a href="{{ url('server/voucher/report') }}" class="btn {{ $orders ? 'btn-secondary' : 'btn-primary' }} d-inline-block"><i class="uil-invoice"></i> Laporan Penggunaan</a>
                        <a href="{{ url('server/voucher/report/orders') }}" class="btn {{ $orders ? 'btn-primary' : 'btn-secondary' }} d-inline-block"><i class="uil-money-bill-stack"></i> Laporan Pembelian</a>
                    </div>
                </div>
            </div>
        </div>

        @unless ($showData)
            <div class="card">
                <div class="card-body">
                    <form method="GET">
                        <button type="submit" name="show_data" value="1" class="btn btn-primary"><i class="uil uil-eye me-1"></i> Tampilkan Data</button>
                    </form>
                    <p class="text-muted mb-0 mt-2">Klik Tampilkan Data untuk memuat laporan.</p>
                </div>
            </div>
        @endunless

        @if ($showData)
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label class="form-label">Periode : </label>
                                    <div class="row g-3">
                                        <div class="col-xl-6">
                                            <select class="form-select" name="bulan" id="bulan">
                                                <option value="01">Januari</option>
                                                <option value="02">Februari</option>
                                                <option value="03">Maret</option>
                                                <option value="04">April</option>
                                                <option value="05">Mei</option>
                                                <option value="06">Juni</option>
                                                <option value="07">Juli</option>
                                                <option value="08">Agustus</option>
                                                <option value="09">September</option>
                                                <option value="10">Oktober</option>
                                                <option value="11">November</option>
                                                <option value="12">Desember</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-6">
                                            <select class="form-select" name="tahun" id="tahun">
                                                @forelse ($tahun as $row)
                                                    <option value="{{ $row->tahun }}">{{ $row->tahun }}</option>
                                                @empty
                                                    <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                                @endforelse
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <button class="btn btn-primary d-inline-block" id="filterButton"><i class="uil-search"></i> Filter</button>
                        <button class="btn btn-success d-inline-block" id="refreshButton" name="refresh"><i class="uil-refresh"></i> Refresh</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="filteredData" style="display: none;">
            <div class="col-xl-12">
                <div class="card-header">
                    <h3 class="card-title"> <i class="uil-chart-growth"></i> Laporan</h3>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div id="container"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <table id="data-table" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    @unless ($orders)
                                        <th>Waktu</th>
                                    @endunless
                                    <th>Kode Voucher</th>
                                    <th>Paket</th>
                                    <th>Harga</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
@if ($showData)
<script src="{{ asset('assets/js/highcharts.js') }}"></script>
<script src="{{ asset('assets/js/highcharts-theme.js') }}"></script>
<script>
    const currentDate = new Date();
    const currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
    const selectBulan = document.getElementById('bulan');
    selectBulan.value = currentMonth;

    const refreshButton = document.getElementById('refreshButton');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            location.reload();
        });
    }

    $(document).ready(function() {
        var tableInstance;
        var isOrders = {{ $orders ? 'true' : 'false' }};

        $('#filterButton').click(function() {
            var preloader = $('#preloader');
            var bulan = $('#bulan').val();
            var tahun = $('#tahun').val();

            preloader.show();

            $.ajax({
                url: '{{ $filterUrl }}',
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    show_data: 1,
                    bulan: bulan,
                    tahun: tahun
                },
                success: function(data) {
                    const filteredTable = $('#filteredData table tbody');
                    filteredTable.empty();

                    if (typeof data.data === 'string' && data.data === 'No data found') {
                        filteredTable.html('<tr><td colspan="6">No data found</td></tr>');
                        drawChart([], data.daysInMonth, data.month, data.year);
                        if (tableInstance) {
                            tableInstance.clear().draw();
                        }
                    } else if (data.data.length > 0) {
                        drawChart(data.chartData, data.daysInMonth);

                        var tableData = [];
                        $.each(data.data, function(index, row) {
                            var rawDate = row.date;
                            var date = new Date(rawDate);
                            var formattedDate = date.toLocaleDateString('id-ID', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric'
                            });

                            var rowData = isOrders ? [
                                formattedDate,
                                row.kode,
                                row.service,
                                formatRupiah(row.harga)
                            ] : [
                                formattedDate,
                                row.time,
                                row.kode,
                                row.service,
                                formatRupiah(row.harga)
                            ];
                            tableData.push(rowData);
                        });

                        var columns = isOrders ? [
                            { title: 'Tanggal' },
                            { title: 'Kode Voucher' },
                            { title: 'Paket' },
                            { title: 'Harga' }
                        ] : [
                            { title: 'Tanggal' },
                            { title: 'Waktu' },
                            { title: 'Kode Voucher' },
                            { title: 'Paket' },
                            { title: 'Harga' }
                        ];

                        if (!tableInstance) {
                            tableInstance = $('#data-table').DataTable({
                                data: tableData,
                                columns: columns
                            });
                        } else {
                            tableInstance.clear().draw();
                            tableInstance.rows.add(tableData).draw();
                        }
                    } else {
                        filteredTable.html('<tr><td colspan="6">No data found</td></tr>');
                    }

                    preloader.hide();
                    $('#filteredData').css('display', 'block');
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    preloader.hide();
                }
            });
        });
    });

    function drawChart(reportByDay, daysInMonth, selectedMonth, selectedYear) {
        var dates = [];
        var total_harga = [];
        var total_voucher = [];
        var month = selectedMonth;
        var year = selectedYear;

        if (reportByDay.length > 0) {
            var firstDate = reportByDay[0].date;
            var dateParts = firstDate.split('-');
            year = parseInt(dateParts[0]);
            month = parseInt(dateParts[1]);
        }

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
            chart: { type: 'area' },
            title: { text: 'Grafik Laporan' },
            xAxis: { categories: dates },
            yAxis: { title: { text: 'Total Penjualan' } },
            legend: {
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom'
            },
            tooltip: {
                formatter: function() {
                    var dateString = dates[this.point.x];
                    var parts = dateString.split(' ');
                    return 'Tanggal: <b>' + parts[0] + ' ' + parts[1] + '</b><br>' +
                        'Total Penjualan: <b>Rp ' + this.y.toLocaleString() + '</b><br>' +
                        'Total Voucher: <b>' + total_voucher[this.point.index] + '</b>';
                }
            },
            series: [{
                name: 'Total Penjualan',
                data: total_harga
            }]
        });
    }

    function formatRupiah(angka) {
        var number_string = angka.toString();
        var split = number_string.split(',');
        var sisa = split[0].length % 3;
        var rupiah = split[0].substr(0, sisa);
        var ribuan = split[0].substr(sisa).match(/\d{3}/g);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return 'Rp ' + rupiah;
    }
</script>
@endif
@endsection
