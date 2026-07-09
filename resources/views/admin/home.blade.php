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

            <div class="row" id="mikrotik-section">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h4 class="card-title mb-0"><i class="uil uil-server-network me-1"></i>Statistik MikroTik</h4>
                            <button type="button" id="mikrotik-refresh" class="btn btn-sm btn-soft-primary">
                                <i class="uil uil-sync me-1"></i>Muat Statistik
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="mikrotik-summary" class="row g-3 mb-3 d-none">
                                <div class="col-6 col-md-3">
                                    <div class="mikrotik-summary-tile">
                                        <span class="mikrotik-summary-label">Router Online</span>
                                        <h4 class="mb-0"><span id="mk-online">0</span> / <span id="mk-total">0</span></h4>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="mikrotik-summary-tile">
                                        <span class="mikrotik-summary-label">Router Offline</span>
                                        <h4 class="mb-0 text-danger" id="mk-offline">0</h4>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="mikrotik-summary-tile">
                                        <span class="mikrotik-summary-label">PPPoE Aktif</span>
                                        <h4 class="mb-0 text-success" id="mk-ppp">0</h4>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="mikrotik-summary-tile">
                                        <span class="mikrotik-summary-label">Hotspot Aktif</span>
                                        <h4 class="mb-0 text-info" id="mk-hotspot">0</h4>
                                    </div>
                                </div>
                            </div>

                            <div id="mikrotik-placeholder" class="text-center py-4">
                                @if ($routers->isEmpty())
                                    <p class="text-muted mb-0"><i class="uil uil-server-network-alt me-1"></i>Belum ada Mikrotik yang terdaftar.</p>
                                @else
                                    <p class="text-muted mb-0">Klik <strong>Muat Statistik</strong> untuk menampilkan kondisi {{ $routers->count() }} Mikrotik.</p>
                                @endif
                            </div>

                            <div id="mikrotik-loading" class="text-center py-5 d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Menghubungi Mikrotik, mohon tunggu...</p>
                            </div>

                            <div id="mikrotik-cards" class="row g-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .mikrotik-summary-tile {
            border: 1px solid rgba(91, 115, 232, .12);
            border-radius: 18px;
            padding: 16px 18px;
            background: linear-gradient(135deg, rgba(91, 115, 232, .08), rgba(52, 195, 143, .04));
            box-shadow: 0 12px 28px rgba(15, 23, 42, .05);
        }

        .mikrotik-summary-label {
            display: block;
            margin-bottom: 6px;
            color: #74788d;
            font-size: .78rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .mikrotik-card {
            position: relative;
            overflow: hidden;
            border: 0;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(91, 115, 232, .18), transparent 36%),
                linear-gradient(145deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 18px 44px rgba(15, 23, 42, .08);
        }

        .mikrotik-card::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image: linear-gradient(rgba(91, 115, 232, .06) 1px, transparent 1px), linear-gradient(90deg, rgba(91, 115, 232, .06) 1px, transparent 1px);
            background-size: 28px 28px;
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .8), transparent 72%);
        }

        .mikrotik-card .card-body {
            position: relative;
            z-index: 1;
        }

        .mikrotik-badge {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .mikrotik-metric {
            border-radius: 16px;
            padding: 12px;
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(116, 120, 141, .12);
        }

        .mikrotik-metric span {
            display: block;
            color: #74788d;
            font-size: .75rem;
        }

        .mikrotik-metric strong {
            color: #343a40;
            font-size: 1.05rem;
        }
    </style>
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

        // ---------- Statistik MikroTik (async) ----------
        const mikrotikUrl = '{{ url(request()->segment(1)."/dashboard/mikrotik") }}';
        const mkRefreshBtn = document.getElementById('mikrotik-refresh');
        const mkPlaceholder = document.getElementById('mikrotik-placeholder');
        const mkLoading = document.getElementById('mikrotik-loading');
        const mkCards = document.getElementById('mikrotik-cards');
        const mkSummary = document.getElementById('mikrotik-summary');
        const mkGauges = [];
        let mkLoadingInProgress = false;
        let mkHasLoaded = false;
        let mkAutoTimer = null;

        function gaugeOptions(value, color, label) {
            return {
                chart: { type: 'radialBar', height: 140, sparkline: { enabled: true } },
                series: [value],
                labels: [label],
                colors: [color],
                plotOptions: {
                    radialBar: {
                        hollow: { size: '55%' },
                        track: { background: 'rgba(116,120,141,.12)' },
                        dataLabels: {
                            name: { offsetY: 18, fontSize: '11px', color: '#74788d' },
                            value: { offsetY: -14, fontSize: '18px', fontWeight: 600, formatter: v => Math.round(v) + '%' }
                        }
                    }
                },
                stroke: { lineCap: 'round' }
            };
        }

        function destroyGauges() {
            while (mkGauges.length) {
                try { mkGauges.pop().destroy(); } catch (e) { /* noop */ }
            }
        }

        function statusBadge(online) {
            return online
                ? '<span class="mikrotik-badge bg-soft-success text-success"><i class="uil uil-check-circle"></i> Online</span>'
                : '<span class="mikrotik-badge bg-soft-danger text-danger"><i class="uil uil-times-circle"></i> Offline</span>';
        }

        function renderMikrotikCard(router, index) {
            const col = document.createElement('div');
            col.className = 'col-md-6 col-xl-4';

            if (!router.online) {
                col.innerHTML =
                    '<div class="card mikrotik-card h-100"><div class="card-body">' +
                    '<div class="d-flex align-items-center justify-content-between mb-2">' +
                    '<h5 class="mb-0">' + escapeHtml(router.nama) + '</h5>' + statusBadge(false) + '</div>' +
                    '<p class="text-muted mb-3"><i class="uil uil-server"></i> ' + escapeHtml(router.ip) + '</p>' +
                    '<div class="alert alert-danger mb-0 py-2"><i class="uil uil-exclamation-triangle me-1"></i>Tidak dapat terhubung ke Mikrotik.</div>' +
                    '</div></div>';
                mkCards.appendChild(col);
                return;
            }

            col.innerHTML =
                '<div class="card mikrotik-card h-100"><div class="card-body">' +
                '<div class="d-flex align-items-center justify-content-between mb-1">' +
                '<h5 class="mb-0">' + escapeHtml(router.nama) + '</h5>' + statusBadge(true) + '</div>' +
                '<p class="text-muted mb-2"><i class="uil uil-server"></i> ' + escapeHtml(router.ip) +
                ' &middot; ' + escapeHtml(router.model) + ' &middot; v' + escapeHtml(router.version) + '</p>' +
                '<div class="row text-center g-2 mb-3">' +
                '<div class="col-6"><div id="mk-cpu-' + index + '"></div></div>' +
                '<div class="col-6"><div id="mk-mem-' + index + '"></div></div>' +
                '</div>' +
                '<div class="row g-2">' +
                '<div class="col-4"><div class="mikrotik-metric text-center"><span>PPPoE</span><strong>' + router.pppActive + '/' + router.pppTotal + '</strong></div></div>' +
                '<div class="col-4"><div class="mikrotik-metric text-center"><span>Hotspot</span><strong>' + router.hotspotActive + '</strong></div></div>' +
                '<div class="col-4"><div class="mikrotik-metric text-center"><span>Uptime</span><strong>' + escapeHtml(router.uptime) + '</strong></div></div>' +
                '</div>' +
                '<p class="text-muted small mb-0 mt-2 text-center">RAM ' + escapeHtml(router.memUsed) + ' / ' + escapeHtml(router.memTotal) + '</p>' +
                '</div></div>';

            mkCards.appendChild(col);

            const cpuGauge = new ApexCharts(col.querySelector('#mk-cpu-' + index), gaugeOptions(router.cpu, '#5b73e8', 'CPU'));
            const memGauge = new ApexCharts(col.querySelector('#mk-mem-' + index), gaugeOptions(router.memUsedPercent, '#f1b44c', 'Memori'));
            cpuGauge.render();
            memGauge.render();
            mkGauges.push(cpuGauge, memGauge);
        }

        function loadMikrotikStats() {
            if (mkLoadingInProgress) return;
            mkLoadingInProgress = true;

            // Load pertama: tampilkan spinner + kosongkan panel. Auto-refresh
            // berikutnya (silent) hanya perbarui data supaya tidak berkedip.
            const silent = mkHasLoaded;
            if (!silent) {
                destroyGauges();
                mkCards.innerHTML = '';
                mkPlaceholder.classList.add('d-none');
                mkSummary.classList.add('d-none');
                mkLoading.classList.remove('d-none');
                mkRefreshBtn.setAttribute('disabled', 'disabled');
            }

            fetch(mikrotikUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(res => {
                    mkLoading.classList.add('d-none');

                    if (!res.data || res.data.length === 0) {
                        mkPlaceholder.classList.remove('d-none');
                        mkPlaceholder.innerHTML = '<p class="text-muted mb-0">Belum ada Mikrotik yang terdaftar.</p>';
                        return;
                    }

                    document.getElementById('mk-online').textContent = res.summary.online;
                    document.getElementById('mk-total').textContent = res.summary.total;
                    document.getElementById('mk-offline').textContent = res.summary.offline;
                    document.getElementById('mk-ppp').textContent = res.summary.pppActive;
                    document.getElementById('mk-hotspot').textContent = res.summary.hotspotActive;
                    mkSummary.classList.remove('d-none');

                    // Rebuild kartu (destroy dulu gauge lama agar tidak bocor memori).
                    destroyGauges();
                    mkCards.innerHTML = '';
                    res.data.forEach((router, i) => renderMikrotikCard(router, i));

                    mkHasLoaded = true;
                    mkRefreshBtn.innerHTML = '<i class="uil uil-sync me-1"></i>Muat Ulang';
                    startMikrotikAutoRefresh();
                })
                .catch(() => {
                    mkLoading.classList.add('d-none');
                    if (!silent) {
                        mkPlaceholder.classList.remove('d-none');
                        mkPlaceholder.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat statistik Mikrotik. Coba lagi.</div>';
                    }
                })
                .finally(() => {
                    mkLoadingInProgress = false;
                    mkRefreshBtn.removeAttribute('disabled');
                });
        }

        function startMikrotikAutoRefresh() {
            if (mkAutoTimer) return;
            // Refresh tiap 5 detik. Berhenti saat tab tidak terlihat agar tidak
            // membebani Mikrotik, lanjut lagi saat tab kembali aktif.
            mkAutoTimer = setInterval(() => {
                if (!document.hidden) {
                    loadMikrotikStats();
                }
            }, 5000);
        }

        if (mkRefreshBtn) {
            mkRefreshBtn.addEventListener('click', loadMikrotikStats);
        }
    </script>
@endsection
