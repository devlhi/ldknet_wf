@extends('admin.layout')

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Absensi Hari Ini</h4>
                        <span class="text-muted">{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>
            </div>

            @if (session('auth_errors'))
                <div class="alert alert-danger" role="alert">
                    @foreach (session('auth_errors') as $err)
                        {{ $err }}
                    @endforeach
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>
                    @foreach (session('success') as $suc)
                        {{ $suc }}
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-3">Status Absen</h5>
                            <div class="row">
                                <div class="col-6 border-end">
                                    <p class="text-muted mb-1">Check In</p>
                                    <h4 class="{{ optional($attendance)->check_in ? 'text-success' : 'text-muted' }}">
                                        {{ optional(optional($attendance)->check_in)->format('H:i') ?? '--:--' }}
                                    </h4>
                                </div>
                                <div class="col-6">
                                    <p class="text-muted mb-1">Check Out</p>
                                    <h4 class="{{ optional($attendance)->check_out ? 'text-danger' : 'text-muted' }}">
                                        {{ optional(optional($attendance)->check_out)->format('H:i') ?? '--:--' }}
                                    </h4>
                                </div>
                            </div>
                            @if ($attendance)
                                <div class="mt-3">
                                    <span class="badge bg-info text-capitalize">Status: {{ $attendance->status }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Aksi Absen</h5>

                            <div id="gpsStatus"></div>

                            @if (! $attendance)
                                <form action="{{ url('karyawan/absensi/check-in') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="lat" id="lat-in">
                                    <input type="hidden" name="lng" id="lng-in">
                                    <div class="mb-3">
                                        <label class="form-label">Status Kehadiran</label>
                                        <select name="status" class="form-select" required>
                                            <option value="hadir">Hadir</option>
                                            <option value="izin">Izin</option>
                                            <option value="sakit">Sakit</option>
                                            <option value="cuti">Cuti</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Foto (opsional)</label>
                                        <input type="file" name="foto" accept="image/*" capture="user" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Keterangan (opsional)</label>
                                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 btn-absen-submit"><i class="uil uil-sign-in-alt me-1"></i> Simpan Absensi (Check In)</button>
                                </form>
                            @elseif ($attendance->status !== 'hadir')
                                <div class="alert alert-info mb-0 text-center">
                                    <i class="uil uil-info-circle"></i> Absensi hari ini tercatat sebagai <b class="text-capitalize">{{ $attendance->status }}</b>.
                                </div>
                            @elseif (! $attendance->check_out)
                                <form action="{{ url('karyawan/absensi/check-out') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="lat" id="lat-out">
                                    <input type="hidden" name="lng" id="lng-out">
                                    <div class="mb-3">
                                        <label class="form-label">Foto (opsional)</label>
                                        <input type="file" name="foto" accept="image/*" capture="user" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-danger w-100 btn-absen-submit"><i class="uil uil-sign-out-alt me-1"></i> Check Out</button>
                                </form>
                            @else
                                <div class="alert alert-success mb-0 text-center">
                                    <i class="uil uil-check-circle"></i> Absensi hari ini sudah lengkap. Terima kasih!
                                </div>
                            @endif

                            <a href="{{ url('karyawan/absensi/history') }}" class="btn btn-light w-100 mt-2"><i class="uil uil-history me-1"></i> Riwayat Absensi</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            var enforce = @json($attendanceSetting['enforce'] ?? false);
            var officeLat = @json($attendanceSetting['latitude'] ?? null);
            var officeLng = @json($attendanceSetting['longitude'] ?? null);
            var radius = @json($attendanceSetting['radius_meter'] ?? null);
            var hasOfficeLocation = enforce && officeLat !== null && officeLng !== null;

            var statusSelect = document.querySelector('select[name="status"]'); // ada di form check-in saja
            var gpsBox = document.getElementById('gpsStatus');
            var submitBtn = document.querySelector('.btn-absen-submit');

            var gps = { lat: null, lng: null, error: null };

            function needsLocationCheck() {
                if (!hasOfficeLocation) return false;
                // Form check-in: pembatasan hanya berlaku saat status "Hadir".
                // Form check-out: selalu berlaku (hanya tampil setelah check-in hadir).
                return statusSelect ? statusSelect.value === 'hadir' : true;
            }

            function haversine(lat1, lng1, lat2, lng2) {
                var R = 6371000;
                var dLat = (lat2 - lat1) * Math.PI / 180;
                var dLng = (lng2 - lng1) * Math.PI / 180;
                var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLng / 2) * Math.sin(dLng / 2);
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            }

            function setLatLngInputs(lat, lng) {
                ['lat-in', 'lat-out'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.value = lat;
                });
                ['lng-in', 'lng-out'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.value = lng;
                });
            }

            function render() {
                if (!submitBtn) return;

                if (!needsLocationCheck()) {
                    if (gpsBox) gpsBox.innerHTML = '';
                    submitBtn.disabled = false;
                    return;
                }

                if (gps.error) {
                    if (gpsBox) gpsBox.innerHTML = '<div class="alert alert-danger mb-3">' + gps.error + '</div>';
                    submitBtn.disabled = true;
                    return;
                }

                if (gps.lat === null) {
                    if (gpsBox) gpsBox.innerHTML = '<div class="alert alert-info mb-3"><span class="spinner-border spinner-border-sm me-1"></span> Mendeteksi lokasi Anda...</div>';
                    submitBtn.disabled = true;
                    return;
                }

                var dist = haversine(gps.lat, gps.lng, officeLat, officeLng);
                var within = dist <= radius;
                if (gpsBox) {
                    gpsBox.innerHTML = '<div class="alert ' + (within ? 'alert-success' : 'alert-danger') + ' mb-3">' +
                        (within ? '<i class="uil uil-check-circle"></i> Anda berada dalam radius absen' : '<i class="uil uil-times-circle"></i> Anda di luar radius absen') +
                        ' (jarak ' + Math.round(dist) + ' m, maksimal ' + radius + ' m)</div>';
                }
                submitBtn.disabled = !within;
            }

            if (statusSelect) statusSelect.addEventListener('change', render);
            if (hasOfficeLocation && submitBtn) submitBtn.disabled = needsLocationCheck();

            if (!navigator.geolocation) {
                gps.error = 'Perangkat/browser tidak mendukung deteksi lokasi.' + (hasOfficeLocation ? ' Absen tidak bisa dilakukan dari perangkat ini.' : '');
                render();
                return;
            }

            render();
            navigator.geolocation.getCurrentPosition(function(pos) {
                gps.lat = pos.coords.latitude;
                gps.lng = pos.coords.longitude;
                setLatLngInputs(gps.lat, gps.lng);
                render();
            }, function() {
                gps.error = 'Gagal mendapatkan lokasi GPS. Aktifkan izin lokasi lalu muat ulang halaman.';
                render();
            }, { enableHighAccuracy: true, timeout: 10000 });
        })();
    </script>
@endsection
