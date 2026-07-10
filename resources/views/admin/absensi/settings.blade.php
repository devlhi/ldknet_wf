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
                        <p class="text-muted mb-3">Cari nama lokasi, klik titik di peta, atau ketik koordinat manual untuk menentukan lokasi kantor/lokasi absen. Karyawan hanya bisa check-in/check-out dalam radius ini.</p>

                        <div class="position-relative mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="uil uil-search"></i></span>
                                <input type="text" class="form-control" id="locationSearch" placeholder="Cari nama lokasi/alamat... (mis. Kantor Pos Ngabang)" autocomplete="off">
                            </div>
                            <div id="searchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; max-height: 260px; overflow-y: auto; display: none;"></div>
                        </div>

                        <div id="mapPicker" style="height: 380px; border-radius: 6px;"></div>

                        <form method="post" action="{{ url('admin/absensi/pengaturan') }}" class="mt-3">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="latInput" name="latitude" value="{{ old('latitude', $setting->latitude ?? '') }}" placeholder="Contoh: -0.4512300" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="lngInput" name="longitude" value="{{ old('longitude', $setting->longitude ?? '') }}" placeholder="Contoh: 109.7891200" required>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-3" style="margin-top: -0.75rem;">Bisa diisi manual, hasil klik peta, atau hasil pilih dari pencarian lokasi di atas — ketiganya saling menyinkronkan peta.</small>

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
                            <li>Ketik nama lokasi/alamat di kotak pencarian lalu pilih dari daftar hasil untuk auto-isi koordinat, atau klik peta, atau ketik langsung nilai Latitude/Longitude.</li>
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
var labelInput = document.querySelector('input[name="label"]');
var radiusInput = document.querySelector('input[name="radius_meter"]');

// true selama kode sendiri yang mengisi latInput/lngInput (klik peta / pilih hasil
// pencarian), supaya listener "input" manual di bawah tidak menggambar ulang peta
// dua kali / bertabrakan dengan sumber perubahan yang sama.
var syncingFromMap = false;

function drawPoint(lat, lng, radius, recenter) {
    if (marker) picker.removeLayer(marker);
    if (circle) picker.removeLayer(circle);
    marker = L.marker([lat, lng]).addTo(picker);
    circle = L.circle([lat, lng], { radius: radius, color: '#556ee6', fillOpacity: 0.15 }).addTo(picker);
    syncingFromMap = true;
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
    syncingFromMap = false;
    if (recenter) picker.setView([lat, lng], Math.max(picker.getZoom(), 15));
}

picker.on('click', function(e) {
    drawPoint(e.latlng.lat, e.latlng.lng, parseInt(radiusInput.value, 10) || 100, false);
});

radiusInput.addEventListener('input', function() {
    if (circle) circle.setRadius(parseInt(radiusInput.value, 10) || 100);
});

// Ketik manual Latitude/Longitude -> peta ikut pindah (dua arah dengan klik peta).
function handleManualLatLng() {
    if (syncingFromMap) return;
    var lat = parseFloat(latInput.value);
    var lng = parseFloat(lngInput.value);
    if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
    drawPoint(lat, lng, parseInt(radiusInput.value, 10) || 100, true);
}
latInput.addEventListener('change', handleManualLatLng);
lngInput.addEventListener('change', handleManualLatLng);

if (initialLat && initialLng) {
    drawPoint(initialLat, initialLng, initialRadius, false);
}

// ---------- Pencarian nama lokasi (Nominatim/OpenStreetMap, gratis tanpa API key) ----------
var searchInput = document.getElementById('locationSearch');
var searchResults = document.getElementById('searchResults');
var searchDebounce = null;
var searchAbort = null;

function hideResults() {
    searchResults.style.display = 'none';
    searchResults.innerHTML = '';
}

function renderResults(items) {
    searchResults.innerHTML = '';
    if (!items.length) {
        hideResults();
        return;
    }
    items.forEach(function(item) {
        var el = document.createElement('button');
        el.type = 'button';
        el.className = 'list-group-item list-group-item-action';
        el.textContent = item.display_name; // textContent -> aman dari HTML injection
        el.addEventListener('click', function() {
            var lat = parseFloat(item.lat);
            var lng = parseFloat(item.lon);
            drawPoint(lat, lng, parseInt(radiusInput.value, 10) || 100, true);
            searchInput.value = item.display_name;
            if (!labelInput.value) {
                // Auto-isi Nama Lokasi dari hasil pencarian bila kolomnya masih kosong.
                labelInput.value = item.display_name.split(',')[0];
            }
            hideResults();
        });
        searchResults.appendChild(el);
    });
    searchResults.style.display = 'block';
}

function searchLocation(query) {
    if (searchAbort) searchAbort.abort();
    searchAbort = new AbortController();

    var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=6&countrycodes=id&accept-language=id&q=' + encodeURIComponent(query);

    fetch(url, { signal: searchAbort.signal, headers: { 'Accept': 'application/json' } })
        .then(function(res) { return res.json(); })
        .then(function(data) { renderResults(Array.isArray(data) ? data : []); })
        .catch(function(err) {
            if (err.name !== 'AbortError') hideResults();
        });
}

searchInput.addEventListener('input', function() {
    var query = searchInput.value.trim();
    clearTimeout(searchDebounce);
    if (query.length < 3) {
        hideResults();
        return;
    }
    // Debounce ~500ms mengikuti kebijakan pemakaian wajar Nominatim (maks 1 request/detik).
    searchDebounce = setTimeout(function() { searchLocation(query); }, 500);
});

searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchDebounce);
        var query = searchInput.value.trim();
        if (query.length >= 3) searchLocation(query);
    } else if (e.key === 'Escape') {
        hideResults();
    }
});

document.addEventListener('click', function(e) {
    if (e.target !== searchInput && !searchResults.contains(e.target)) hideResults();
});
</script>
@endsection
