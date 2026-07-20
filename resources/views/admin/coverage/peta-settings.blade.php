@extends('admin.layout')

@section('css')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <style>
        .hub-pin { font-size: 24px; line-height: 1; filter: drop-shadow(0 1px 2px rgba(0,0,0,.4)); text-align: center; }
        #pickMap { height: 460px; border-radius: 6px; }
    </style>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Pengaturan Peta Jaringan</h4>
                        <a href="{{ url('admin/coverage/peta') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i> Ke Peta</a>
                    </div>
                </div>
            </div>

            @if (session('auth_errors'))
                <div class="alert alert-danger">@foreach (session('auth_errors') as $err)<p class="mb-0">{{ $err }}</p>@endforeach</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>@foreach (session('success') as $suc){{ $suc }}@endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="post" action="{{ url('admin/coverage/peta/pengaturan') }}">
                @csrf
                <div class="row">
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-body">
                                <div class="position-relative mb-2">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                        <input type="text" class="form-control" id="locSearch" placeholder="Cari nama lokasi/area... (mis. Ngabang, Landak)" autocomplete="off">
                                    </div>
                                    <div id="searchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:1000;max-height:240px;overflow-y:auto;display:none;"></div>
                                </div>
                                <div id="pickMap"></div>
                                <small class="text-muted d-block mt-2"><i class="mdi mdi-information-outline"></i> <strong>Klik peta</strong> untuk menaruh titik pusat/OLT (📡). Geser &amp; zoom peta ke area yang diinginkan lalu klik <em>“Jadikan tampilan default”</em> untuk menyimpan area tampilan.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Titik Pusat / OLT</h5>
                                <div class="mb-3">
                                    <label class="form-label">Nama titik pusat</label>
                                    <input type="text" name="hub_label" maxlength="100" class="form-control" value="{{ old('hub_label', $setting->hub_label) }}" placeholder="mis. OLT Kantor Pusat">
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Latitude pusat</label>
                                        <input type="text" name="hub_lat" id="hubLat" class="form-control" value="{{ old('hub_lat', $setting->hub_lat) }}" placeholder="mis. 0.3776">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Longitude pusat</label>
                                        <input type="text" name="hub_lng" id="hubLng" class="form-control" value="{{ old('hub_lng', $setting->hub_lng) }}" placeholder="mis. 109.9576">
                                    </div>
                                </div>

                                <hr>
                                <h5 class="card-title mb-3">Tampilan Default Peta</h5>
                                <div class="mb-3">
                                    <label class="form-label">Basemap default</label>
                                    <select name="basemap" id="basemapSel" class="form-select">
                                        <option value="streets" {{ $setting->basemap === 'streets' ? 'selected' : '' }}>Jalan (OSM)</option>
                                        <option value="satelit" {{ $setting->basemap === 'satelit' ? 'selected' : '' }}>Satelit</option>
                                        <option value="topografi" {{ $setting->basemap === 'topografi' ? 'selected' : '' }}>Topografi</option>
                                        <option value="gelap" {{ $setting->basemap === 'gelap' ? 'selected' : '' }}>Mode gelap</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Area tampilan (center &amp; zoom)</label>
                                    <div class="d-flex gap-2">
                                        <input type="text" name="center_lat" id="centerLat" class="form-control" value="{{ old('center_lat', $setting->center_lat) }}" placeholder="lat">
                                        <input type="text" name="center_lng" id="centerLng" class="form-control" value="{{ old('center_lng', $setting->center_lng) }}" placeholder="lng">
                                        <input type="number" name="zoom" id="zoomVal" class="form-control" style="max-width:90px" min="1" max="20" value="{{ old('zoom', $setting->zoom) }}" placeholder="zoom">
                                    </div>
                                    <button type="button" id="captureView" class="btn btn-sm btn-outline-primary mt-2"><i class="mdi mdi-crosshairs-gps me-1"></i> Jadikan tampilan peta saat ini sebagai default</button>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3 w-100"><i class="mdi mdi-content-save me-1"></i> Simpan Pengaturan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
(function () {
    function tl(url, opts) { return L.tileLayer(url, Object.assign({ maxZoom: 19 }, opts || {})); }
    var basemaps = {
        streets: tl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }),
        satelit: tl('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' }),
        topografi: tl('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenTopoMap', maxZoom: 17 }),
        gelap: tl('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', { attribution: '&copy; CARTO' })
    };

    var centerLat = document.getElementById('centerLat');
    var centerLng = document.getElementById('centerLng');
    var zoomVal = document.getElementById('zoomVal');
    var hubLat = document.getElementById('hubLat');
    var hubLng = document.getElementById('hubLng');
    var labelInput = document.querySelector('input[name="hub_label"]');
    var basemapSel = document.getElementById('basemapSel');

    var parsedStartLat = parseFloat(centerLat.value);
    var parsedStartLng = parseFloat(centerLng.value);
    var parsedStartZoom = parseInt(zoomVal.value, 10);
    var startLat = Number.isFinite(parsedStartLat) ? parsedStartLat : 0.3;
    var startLng = Number.isFinite(parsedStartLng) ? parsedStartLng : 109.5;
    var startZoom = Number.isFinite(parsedStartZoom) ? parsedStartZoom : 11;

    var current = basemaps[basemapSel.value] || basemaps.streets;
    var map = L.map('pickMap', { center: [startLat, startLng], zoom: startZoom, layers: [current] });

    basemapSel.addEventListener('change', function () {
        map.removeLayer(current);
        current = basemaps[basemapSel.value] || basemaps.streets;
        map.addLayer(current);
    });

    var hubIcon = L.divIcon({ className: 'hub-icon', html: '<div class="hub-pin">📡</div>', iconSize: [30, 30], iconAnchor: [15, 15] });
    var hubMarker = null;
    function setHub(lat, lng) {
        if (hubMarker) map.removeLayer(hubMarker);
        hubMarker = L.marker([lat, lng], { icon: hubIcon }).addTo(map);
        hubLat.value = Number(lat).toFixed(7);
        hubLng.value = Number(lng).toFixed(7);
    }
    if (hubLat.value !== '' && hubLng.value !== '') {
        var hl = parseFloat(hubLat.value), hg = parseFloat(hubLng.value);
        if (Number.isFinite(hl) && Number.isFinite(hg)) setHub(hl, hg);
    }

    map.on('click', function (e) { setHub(e.latlng.lat, e.latlng.lng); });

    // Ketik manual lat/lng pusat -> pindahkan marker
    function manualHub() {
        var lat = parseFloat(hubLat.value), lng = parseFloat(hubLng.value);
        if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
        setHub(lat, lng);
        map.setView([lat, lng], Math.max(map.getZoom(), 14));
    }
    hubLat.addEventListener('change', manualHub);
    hubLng.addEventListener('change', manualHub);

    document.getElementById('captureView').addEventListener('click', function () {
        var c = map.getCenter();
        centerLat.value = c.lat.toFixed(7);
        centerLng.value = c.lng.toFixed(7);
        zoomVal.value = map.getZoom();
    });

    // ---------- Pencarian lokasi (Nominatim gratis) ----------
    var searchInput = document.getElementById('locSearch');
    var searchResults = document.getElementById('searchResults');
    var deb = null, ab = null;
    function hideResults() { searchResults.style.display = 'none'; searchResults.innerHTML = ''; }
    function renderResults(items) {
        searchResults.innerHTML = '';
        if (!items.length) { hideResults(); return; }
        items.forEach(function (item) {
            var el = document.createElement('button');
            el.type = 'button'; el.className = 'list-group-item list-group-item-action';
            el.textContent = item.display_name;
            el.addEventListener('click', function () {
                var lat = parseFloat(item.lat), lng = parseFloat(item.lon);
                map.setView([lat, lng], 15);
                setHub(lat, lng);
                if (!labelInput.value) labelInput.value = item.display_name.split(',')[0];
                searchInput.value = item.display_name;
                hideResults();
            });
            searchResults.appendChild(el);
        });
        searchResults.style.display = 'block';
    }
    function doSearch(q) {
        if (ab) ab.abort();
        ab = new AbortController();
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=6&countrycodes=id&accept-language=id&q=' + encodeURIComponent(q), { signal: ab.signal, headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (d) { renderResults(Array.isArray(d) ? d : []); })
            .catch(function (e) { if (e.name !== 'AbortError') hideResults(); });
    }
    searchInput.addEventListener('input', function () {
        var q = searchInput.value.trim();
        clearTimeout(deb);
        if (q.length < 3) { hideResults(); return; }
        deb = setTimeout(function () { doSearch(q); }, 500);
    });
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); clearTimeout(deb); var q = searchInput.value.trim(); if (q.length >= 3) doSearch(q); }
        else if (e.key === 'Escape') hideResults();
    });
    document.addEventListener('click', function (e) {
        if (e.target !== searchInput && !searchResults.contains(e.target)) hideResults();
    });
})();
</script>
@endsection
