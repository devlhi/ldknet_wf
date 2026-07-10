@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Pengaturan Radius Absen</h4>
                </div>
            </div>
        </div>

        @if (session('auth_errors'))
            <div class="alert alert-danger">
                @foreach (session('auth_errors') as $err)
                    <p class="mb-0">{{ $err }}</p>
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
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-1">Lokasi &amp; Radius</h5>
                        <p class="text-muted mb-3">Klik titik di peta untuk menentukan lokasi kantor/lokasi absen. Karyawan hanya bisa check-in/check-out dalam radius ini.</p>

                        <div id="mapPicker" style="height: 380px; border-radius: 6px;"></div>

                        <form method="post" action="{{ url('admin/absensi/pengaturan') }}" class="mt-3">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="latInput" name="latitude" value="{{ old('latitude', $setting->latitude ?? '') }}" readonly required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="lngInput" name="longitude" value="{{ old('longitude', $setting->longitude ?? '') }}" readonly required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Lokasi (opsional)</label>
                                <input type="text" class="form-control" name="label" maxlength="100" value="{{ old('label', $setting->label ?? '') }}" placeholder="Contoh: Kantor Pusat">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Radius (meter)</label>
                                <input type="number" class="form-control" name="radius_meter" min="10" max="5000" value="{{ old('radius_meter', $setting->radius_meter ?? 100) }}" required>
                                <small class="text-muted">Lingkaran radius pada peta ikut menyesuaikan saat nilai ini diubah.</small>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="enforce" id="enforceSwitch" value="1" {{ old('enforce', $setting->enforce ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enforceSwitch">
                                    Aktifkan pembatasan radius (karyawan wajib berada dalam radius untuk absen)
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> Simpan Pengaturan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Cara Kerja</h5>
                        <ul class="text-muted mb-0">
                            <li>Klik peta di sebelah kiri untuk menandai lokasi absen (kantor/tempat kerja).</li>
                            <li>Atur radius toleransi dalam meter — lingkaran biru pada peta menunjukkan area yang diizinkan.</li>
                            <li>Selama <strong>toggle nonaktif</strong>, lokasi GPS karyawan hanya dicatat sebagai info tambahan (tidak memblokir absen) — aman untuk instalasi yang belum mengatur lokasi.</li>
                            <li>Setelah <strong>toggle diaktifkan</strong>, karyawan yang absen di luar radius (atau tidak mengizinkan GPS) akan ditolak sistem dengan pesan jarak yang jelas.</li>
                            <li>Pembatasan berlaku untuk status <strong>Hadir</strong> (check-in &amp; check-out). Status Izin/Sakit/Cuti tidak memerlukan lokasi.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
var initialLat = {{ $setting->latitude ?? 'null' }};
var initialLng = {{ $setting->longitude ?? 'null' }};
var initialRadius = {{ $setting->radius_meter ?? 100 }};

var picker = L.map('mapPicker').setView([initialLat || -0.12, initialLng || 109.15], initialLat ? 15 : 9);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(picker);

var marker = null;
var circle = null;
var latInput = document.getElementById('latInput');
var lngInput = document.getElementById('lngInput');
var radiusInput = document.querySelector('input[name="radius_meter"]');

function drawPoint(lat, lng, radius) {
    if (marker) picker.removeLayer(marker);
    if (circle) picker.removeLayer(circle);
    marker = L.marker([lat, lng]).addTo(picker);
    circle = L.circle([lat, lng], { radius: radius, color: '#556ee6', fillOpacity: 0.15 }).addTo(picker);
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
}

picker.on('click', function(e) {
    drawPoint(e.latlng.lat, e.latlng.lng, parseInt(radiusInput.value, 10) || 100);
});

radiusInput.addEventListener('input', function() {
    if (circle) circle.setRadius(parseInt(radiusInput.value, 10) || 100);
});

if (initialLat && initialLng) {
    drawPoint(initialLat, initialLng, initialRadius);
}
</script>
@endsection
