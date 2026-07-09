@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 col-xl-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="float-end mt-2">
                                <div id="total-revenue-chart"></div>
                            </div>
                            <div>
                                <h4 class="mb-1 mt-1">Rp <span data-plugin="counterup">{{ number_format($credit) }}</span></h4>
                                <p class="text-muted mb-0">Pemasukan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="float-end mt-2">
                                <div id="orders-chart"></div>
                            </div>
                            <div>
                                <h4 class="mb-1 mt-1">Rp {{ number_format($debit) }}</h4>
                                <p class="text-muted mb-0">Pengeluaran</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="float-end mt-2">
                                <div id="customers-chart"></div>
                            </div>
                            <div>
                                <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ number_format($totalpsb) }}</span></h4>
                                <p class="text-muted mb-0">Pelanggan baru</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-white border-bottom">
                            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-finance" role="tab">
                                        <i class="uil uil-money-bill me-1"></i>Keuangan
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-customers" role="tab">
                                        <i class="uil uil-users-alt me-1"></i>Pelanggan Baru
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-transactions" role="tab">
                                        <i class="uil uil-list-ul me-1"></i>Transaksi Terakhir
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active" id="tab-finance" role="tabpanel">
                                    <h4 class="card-title mb-4">Pemasukan vs Pengeluaran {{ now()->year }}</h4>
                                    <div id="finance-chart" class="apex-charts" dir="ltr"></div>
                                </div>

                                <div class="tab-pane" id="tab-customers" role="tabpanel">
                                    <h4 class="card-title mb-4">Pelanggan Baru {{ now()->year }}</h4>
                                    <div id="new-customers-loading" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted mt-2">Memuat data pelanggan baru...</p>
                                    </div>
                                    <div id="new-customers-chart" class="apex-charts d-none" dir="ltr"></div>
                                </div>

                                <div class="tab-pane" id="tab-transactions" role="tabpanel">
                                    <h4 class="card-title mb-4">Transaksi terakhir</h4>
                                    <div id="transactions-content">
                                        <div class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted mt-2">Klik tab ini untuk memuat data transaksi</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-white border-bottom">
                            <h4 class="card-title mb-0"><i class="uil uil-invoice me-1"></i>Status Invoice</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Status Invoice Bulan Ini</p>
                            <div id="invoice-status-chart" class="apex-charts" dir="ltr"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        const chartData = @json($chartData);
        const rupiahFormatter = new Intl.NumberFormat('id-ID');

        new ApexCharts(document.querySelector('#total-revenue-chart'), {
            series: [{ data: chartData.income }],
            fill: { colors: ['#5b73e8'] },
            chart: { type: 'bar', width: 70, height: 40, sparkline: { enabled: true } },
            plotOptions: { bar: { columnWidth: '50%' } },
            tooltip: { y: { formatter: value => 'Rp ' + rupiahFormatter.format(value) } }
        }).render();

        new ApexCharts(document.querySelector('#orders-chart'), {
            series: [{ data: chartData.expense }],
            fill: { colors: ['#f46a6a'] },
            chart: { type: 'bar', width: 70, height: 40, sparkline: { enabled: true } },
            plotOptions: { bar: { columnWidth: '50%' } },
            tooltip: { y: { formatter: value => 'Rp ' + rupiahFormatter.format(value) } }
        }).render();

        new ApexCharts(document.querySelector('#customers-chart'), {
            series: [{ data: chartData.newCustomers }],
            fill: { colors: ['#34c38f'] },
            chart: { type: 'bar', width: 70, height: 40, sparkline: { enabled: true } },
            plotOptions: { bar: { columnWidth: '50%' } },
            tooltip: { y: { formatter: value => rupiahFormatter.format(value) + ' pelanggan' } }
        }).render();

        const financeChart = new ApexCharts(document.querySelector('#finance-chart'), {
            chart: { height: 300, type: 'area', toolbar: { show: false } },
            colors: ['#34c38f', '#f46a6a'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            series: [
                { name: 'Pemasukan', data: chartData.income },
                { name: 'Pengeluaran', data: chartData.expense }
            ],
            xaxis: { categories: chartData.labels },
            yaxis: { labels: { formatter: value => 'Rp ' + rupiahFormatter.format(value) } },
            tooltip: { y: { formatter: value => 'Rp ' + rupiahFormatter.format(value) } },
            legend: { position: 'top' }
        });

        const invoiceStatusChart = new ApexCharts(document.querySelector('#invoice-status-chart'), {
            chart: { height: 300, type: 'donut' },
            labels: chartData.invoiceStatusLabels,
            series: chartData.invoiceStatus,
            colors: ['#34c38f', '#f46a6a', '#f1b44c', '#5b73e8', '#50a5f1'],
            legend: { position: 'bottom' },
            noData: { text: 'Belum ada data invoice' },
            responsive: [{ breakpoint: 480, options: { chart: { height: 280 }, legend: { position: 'bottom' } } }]
        });

        const newCustomersChart = new ApexCharts(document.querySelector('#new-customers-chart'), {
            chart: { height: 300, type: 'bar', toolbar: { show: false } },
            colors: ['#5b73e8'],
            plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
            dataLabels: { enabled: false },
            series: [{ name: 'Pelanggan Baru', data: chartData.newCustomers }],
            xaxis: { categories: chartData.labels },
            yaxis: { labels: { formatter: value => Math.round(value) } },
            tooltip: { y: { formatter: value => rupiahFormatter.format(value) + ' pelanggan' } }
        });

        financeChart.render();
        invoiceStatusChart.render();

        let customersChartRendered = false;
        let transactionsLoaded = false;

        function renderCustomersChart() {
            if (customersChartRendered) return;
            customersChartRendered = true;
            document.getElementById('new-customers-loading').classList.add('d-none');
            document.getElementById('new-customers-chart').classList.remove('d-none');
            newCustomersChart.render();
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>'"]/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#039;',
                '"': '&quot;'
            }[char]));
        }

        function loadTransactions() {
            if (transactionsLoaded) return;
            transactionsLoaded = true;
            const container = document.getElementById('transactions-content');
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="text-muted mt-2">Memuat data transaksi...</p></div>';

            fetch('{{ url(request()->segment(1)."/dashboard/transactions") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(res => {
                if (!res.data || res.data.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center py-4">Belum ada data transaksi.</p>';
                    return;
                }
                let html = '<div class="table-responsive"><table class="table table-centered table-nowrap mb-0"><thead class="table-light"><tr><th>No</th><th>No Invoice</th><th>ID Pelanggan</th><th>Nama Pelanggan</th><th>Tanggal Update</th><th>Pembayaran</th><th>Metode Pembayaran</th><th>Diupdate oleh</th></tr></thead><tbody>';
                res.data.forEach(row => {
                    html += '<tr><td>'+row.no+'</td><td>'+escapeHtml(row.code)+'</td><td>'+escapeHtml(row.idpel)+'</td><td>'+escapeHtml(row.nama)+'</td><td>'+escapeHtml(row.last_update)+'</td><td>'+escapeHtml(row.received)+'</td><td>'+escapeHtml(row.method)+'</td><td>'+escapeHtml(row.update_by)+'</td></tr>';
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;
            })
            .catch(() => {
                transactionsLoaded = false;
                container.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat data transaksi. Coba lagi.</div>';
            });
        }

        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                const target = tab.getAttribute('href');
                if (target === '#tab-customers') {
                    setTimeout(renderCustomersChart, 300);
                }
                if (target === '#tab-transactions') {
                    setTimeout(loadTransactions, 300);
                }
                financeChart.updateOptions({}, false, true);
                invoiceStatusChart.updateOptions({}, false, true);
            });
        });
    </script>
@endsection
