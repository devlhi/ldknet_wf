@extends('admin.layout')

@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    @foreach ($payment as $row)

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Invoice </h4>

                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                @if (session('auth_errors'))
                                    <div class="alert alert-danger alert-message" role="alert">
                                        @foreach (session('auth_errors') as $err)
                                            {{ $err }}
                                        @endforeach
                                    </div>
                                @endif


                                <form action="{{ url('admin/finance/invoice/update') }}" method="POST" enctype="multipart/form-data" id="invoiceConfirmationForm">
                                    @csrf
                                    <input type="hidden" name="target" value="{{ $row->id }}">
                                    <input type="hidden" name="expdate" id="expdate" value="{{ $row->expdate }}">
                                    <input type="hidden" name="code" id="code" value="{{ $row->code }}">

                                    <div class="mb-3">
                                        <label class="form-label">No Invoice</label>
                                        <input type="text" name="invoice" class="form-control" value="{{ $row->code }}" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">ID Pelanggan</label>
                                        <input type="text" name="idpel" class="form-control" value="{{ $row->idpel }}" disabled>
                                        <input type="hidden" name="idpel" value="{{ $row->idpel }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nama</label>
                                        <input type="text" name="user" class="form-control" value="{{ $row->nama }}" disabled>
                                        <input type="hidden" name="user" id="user" value="{{ $row->nama }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Paket</label>
                                        <input type="text" name="package" id="package" class="form-control" value="{{ $row->package }}" disabled>

                                        <input type="hidden" name="package" value="{{ $row->package }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Jumlah yang harus dibayar</label>
                                        <input type="text" name="price" id="price" class="form-control" value="Rp {{ number_format($row->price) }}" disabled>
                                        <input type="hidden" name="price" value="{{ $row->price }}">

                                    </div>

                                    @if (trim((string) $row->reference) !== '')
                                        @php
                                            $gatewayStateClasses = [
                                                'paid' => 'bg-success',
                                                'pending' => 'bg-warning text-dark',
                                                'expired' => 'bg-secondary',
                                                'failed' => 'bg-danger',
                                                'unknown' => 'bg-secondary',
                                            ];
                                            $gatewayStateClass = $gatewayStateClasses[$gatewayStatus['state'] ?? 'unknown'] ?? 'bg-secondary';
                                        @endphp
                                        <div class="card border border-info mb-3" id="gatewayTransactionCard" data-status-url="{{ url('admin/finance/invoice/status/'.$row->code) }}">
                                            <div class="card-header bg-soft-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <strong><i class="uil uil-credit-card"></i> Transaksi Pembayaran Online</strong>
                                                <span class="badge {{ $gatewayStateClass }}" id="gatewayStatusBadge">{{ $gatewayStatus['label'] ?? 'Status tidak diketahui' }}</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-2 small">
                                                    <div class="col-md-4"><strong>Provider:</strong> {{ strtoupper($row->provider ?: '-') }}</div>
                                                    <div class="col-md-8"><strong>Referensi:</strong> <code>{{ $row->reference }}</code></div>
                                                    <div class="col-md-4"><strong>Metode:</strong> {{ $row->method ?: '-' }}</div>
                                                    <div class="col-md-4"><strong>VA/Tujuan:</strong> {{ $row->penerima ?: '-' }}</div>
                                                    <div class="col-md-4"><strong>Nominal online:</strong> Rp {{ number_format((int) ($row->random_price ?: $row->received ?: $row->price)) }}</div>
                                                    <div class="col-md-6"><strong>Berlaku sampai:</strong> {{ $row->exppay ?: 'Tidak diketahui' }}</div>
                                                    <div class="col-md-6"><strong>Status provider:</strong> <span id="gatewayProviderStatus">Belum diperiksa</span></div>
                                                </div>
                                                <div class="mt-3 d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-info" id="refreshGatewayStatus">
                                                        <i class="uil uil-sync"></i> Cek Status Provider
                                                    </button>
                                                    @if ($row->payment_url)
                                                        <a href="{{ $row->payment_url }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                                                            <i class="uil uil-external-link-alt"></i> Buka Halaman Pembayaran
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="alert alert-light border mt-3 mb-0 py-2 d-none" id="gatewayStatusDetails"></div>
                                            </div>
                                        </div>
                                    @endif
                                    @php
                                        $sourcePeriod = \Carbon\CarbonImmutable::parse($row->date)->locale('id')->translatedFormat('F Y');
                                        $nextPeriod = \Carbon\CarbonImmutable::parse($row->date)->addMonthNoOverflow()->locale('id')->translatedFormat('F Y');
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label">Periode Konfirmasi Pembayaran</label>
                                        <div class="border rounded p-3">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="confirmation_period" id="confirmationCurrent" value="current" @checked(old('confirmation_period', 'current') === 'current')>
                                                <label class="form-check-label" for="confirmationCurrent">
                                                    Konfirmasi invoice ini ({{ $sourcePeriod }})
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="confirmation_period" id="confirmationNext" value="next" @checked(old('confirmation_period') === 'next')>
                                                <label class="form-check-label" for="confirmationNext">
                                                    Majukan 1 bulan ke {{ $nextPeriod }}
                                                </label>
                                            </div>
                                            <div class="alert alert-warning mt-3 mb-0 d-none" id="advanceWarning">
                                                Invoice {{ $sourcePeriod }} akan menjadi <strong>Cancel</strong>. Pembayaran dicatat untuk {{ $nextPeriod }} dan masa aktif dihitung satu bulan dari tanggal pembayaran.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Metode Pembayaran</label>
                                        <div>
                                            <select class="form-select" aria-label="Default select example" name="category">
                                                <option value="CASH">CASH</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tersedia</label>
                                        <div>
                                            <select class="form-select" aria-label="Default select example" name="metode">
                                                <option value="Tunai ( Bayar di kantor )">Tunai ( Bayar di kantor )</option>

                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Upload Bukti Pembayaran (Kwitansi)?</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="upload_bukti" id="uploadBuktiYa" value="ya" checked>
                                                <label class="form-check-label" for="uploadBuktiYa">Ya, upload bukti</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="upload_bukti" id="uploadBuktiTidak" value="tidak">
                                                <label class="form-check-label" for="uploadBuktiTidak">Tidak, konfirmasi tanpa bukti</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="buktiWrapper">
                                        <label class="form-label">Bukti Pembayaran ( Kwitansi ) </label>
                                        <div>
                                            <input type="file" class="form-control" name="image" id="image" accept="image/png,image/jpeg">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <select class="form-select" aria-label="Default select example" name="status">
                                                <option value="Paid">Sudah Terbayar</option>
                                            </select>
                                        </div>
                                    </div>

                                    @if ($row->hasActiveGatewayTransaction())
                                        <div class="mb-3 border border-warning rounded p-3 bg-soft-warning">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="bypass_gateway" id="bypassGateway" value="1" @checked(old('bypass_gateway'))>
                                                <label class="form-check-label text-dark fw-semibold" for="bypassGateway">
                                                    Ambil alih transaksi online dan konfirmasi manual
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1">Sistem belum memiliki endpoint pembatalan provider yang dapat diverifikasi. Opsi ini menghentikan transaksi online dari sisi pencatatan invoice dan mencegah callback menggandakan pembayaran, tetapi VA/QR lama mungkin masih dapat dibayar di provider.</small>
                                        </div>
                                    @endif

                                    <div>
                                        <div>
                                            <a href="{{ url('admin/finance/invoice') }}" class="btn btn-secondary waves-effect">
                                                Kembali
                                            </a>
                                            <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                                Update
                                            </button>

                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div> <!-- end col -->
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var wrapper = document.getElementById('buktiWrapper');
            var fileInput = document.getElementById('image');
            var uploadRadios = document.querySelectorAll('input[name="upload_bukti"]');
            var periodRadios = document.querySelectorAll('input[name="confirmation_period"]');
            var advanceWarning = document.getElementById('advanceWarning');
            var form = document.getElementById('invoiceConfirmationForm');
            var gatewayCard = document.getElementById('gatewayTransactionCard');
            var refreshGatewayStatus = document.getElementById('refreshGatewayStatus');
            var gatewayStatusBadge = document.getElementById('gatewayStatusBadge');
            var gatewayProviderStatus = document.getElementById('gatewayProviderStatus');
            var gatewayStatusDetails = document.getElementById('gatewayStatusDetails');
            if (!wrapper || !fileInput) return;

            function gatewayBadgeClass(state) {
                if (state === 'paid') return 'bg-success';
                if (state === 'pending') return 'bg-warning text-dark';
                if (state === 'failed') return 'bg-danger';
                return 'bg-secondary';
            }

            function showGatewayStatus(data) {
                gatewayStatusBadge.className = 'badge ' + gatewayBadgeClass(data.state);
                gatewayStatusBadge.textContent = data.label || 'Status tidak diketahui';
                gatewayProviderStatus.textContent = data.provider_status || '-';
                var details = [];
                if (data.checked_at) details.push('Diperiksa: ' + data.checked_at);
                if (data.amount !== null && data.amount !== undefined) details.push('Nominal provider: Rp ' + Number(data.amount).toLocaleString('id-ID'));
                if (data.message) details.push(data.message);
                gatewayStatusDetails.textContent = details.join(' · ');
                gatewayStatusDetails.classList.toggle('d-none', details.length === 0);
            }

            async function checkGatewayStatus() {
                if (!gatewayCard || !refreshGatewayStatus) return;
                refreshGatewayStatus.disabled = true;
                gatewayProviderStatus.textContent = 'Memeriksa...';
                try {
                    var response = await fetch(gatewayCard.dataset.statusUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    var data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Gagal memeriksa status provider.');
                    showGatewayStatus(data);
                } catch (error) {
                    showGatewayStatus({ state: 'unknown', label: 'Status tidak dapat diperiksa', provider_status: '-', message: error.message });
                } finally {
                    refreshGatewayStatus.disabled = false;
                }
            }

            function syncUpload() {
                var checked = document.querySelector('input[name="upload_bukti"]:checked');
                var wantUpload = checked && checked.value === 'ya';
                wrapper.style.display = wantUpload ? '' : 'none';
                fileInput.required = wantUpload;
                if (!wantUpload) { fileInput.value = ''; }
            }

            function syncPeriod() {
                var checked = document.querySelector('input[name="confirmation_period"]:checked');
                var advance = checked && checked.value === 'next';
                if (advanceWarning) advanceWarning.classList.toggle('d-none', !advance);
            }

            uploadRadios.forEach(function (radio) { radio.addEventListener('change', syncUpload); });
            periodRadios.forEach(function (radio) { radio.addEventListener('change', syncPeriod); });
            if (refreshGatewayStatus) refreshGatewayStatus.addEventListener('click', checkGatewayStatus);
            if (form) {
                form.addEventListener('submit', function (event) {
                    var advance = document.querySelector('input[name="confirmation_period"]:checked')?.value === 'next';
                    var takeover = document.getElementById('bypassGateway')?.checked;
                    if (advance && !window.confirm('Invoice bulan lama akan diubah menjadi Cancel dan pembayaran dimajukan satu bulan. Lanjutkan?')) {
                        event.preventDefault();
                        return;
                    }
                    if (takeover && !window.confirm('Transaksi provider tidak dibatalkan dari sisi Tripay/Duitku dan VA/QR lama mungkin masih dapat dibayar. Tetap ambil alih dan konfirmasi manual?')) {
                        event.preventDefault();
                    }
                });
            }
            syncUpload();
            syncPeriod();
        });
    </script>
@endsection
