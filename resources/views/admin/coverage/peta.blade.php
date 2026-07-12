@extends('admin.layout')

@section('css')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <style>
        /* Animasi aliran cahaya di sepanjang kabel fiber (dash bergerak hub -> ODP) */
        .fiber-flow { animation: fiberdash 1.1s linear infinite; }
        @keyframes fiberdash { to { stroke-dashoffset: -24; } }
        .hub-pin { font-size: 24px; line-height: 1; filter: drop-shadow(0 1px 2px rgba(0,0,0,.4)); text-align: center; }
        #map { height: 620px; border-radius: 6px; }
        .map-hint { position: absolute; z-index: 500; top: 10px; left: 50px; background: rgba(255,255,255,.92); padding: 4px 10px; border-radius: 4px; font-size: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
    </style>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap">
                        <div>
                            <h4 class="mb-0">Peta Jaringan</h4>
                            <p class="text-muted mb-0">Titik pusat/OLT, sebaran ODP, dan jalur kabel fiber mengikuti jalan (beranimasi).</p>
                        </div>
                        <a href="{{ url('admin/coverage/peta/pengaturan') }}" class="btn btn-light"><i class="mdi mdi-cog me-1"></i> Pengaturan Peta</a>
                    </div>
                </div>
            </div>

            @if (! $setting->hub_lat || ! $setting->hub_lng)
                <div class="alert alert-warning">
                    <i class="mdi mdi-information-outline me-1"></i> Titik pusat/OLT belum diatur, jadi jalur kabel belum bisa digambar.
                    <a href="{{ url('admin/coverage/peta/pengaturan') }}" class="alert-link">Atur titik pusat di sini</a>.
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 mb-2 small text-muted">
                        <span><i class="mdi mdi-access-point-network text-danger"></i> Titik Pusat / OLT</span>
                        <span><i class="mdi mdi-circle text-primary"></i> ODP</span>
                        <span><i class="mdi mdi-minus" style="color:#f1b44c"></i> Jalur kabel fiber (mengikuti jalan)</span>
                        <span id="routeStatus" class="text-info"></span>
                    </div>
                    <div class="position-relative">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
(function () {
    var setting = @json($setting);
    var odps = @json($odps);
    var cables = @json($cables);
    var storeUrl = "{{ url('admin/coverage/peta/cable') }}";
    var csrf = "{{ csrf_token() }}";

    var hub = (setting.hub_lat && setting.hub_lng) ? { lat: parseFloat(setting.hub_lat), lng: parseFloat(setting.hub_lng) } : null;

    // ---------- Basemap (semua gratis tanpa API key) ----------
    function tl(url, opts) { return L.tileLayer(url, Object.assign({ maxZoom: 19 }, opts || {})); }
    var basemaps = {
        'Jalan': tl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }),
        'Satelit': tl('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' }),
        'Topografi': tl('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenTopoMap', maxZoom: 17 }),
        'Gelap': tl('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', { attribution: '&copy; CARTO' })
    };
    var defaultName = { streets: 'Jalan', satelit: 'Satelit', topografi: 'Topografi', gelap: 'Gelap' }[setting.basemap] || 'Jalan';

    var map = L.map('map', {
        center: [parseFloat(setting.center_lat), parseFloat(setting.center_lng)],
        zoom: parseInt(setting.zoom, 10) || 11,
        layers: [basemaps[defaultName]]
    });
    L.control.layers(basemaps, null, { collapsed: false }).addTo(map);

    // ---------- Titik pusat / OLT ----------
    if (hub) {
        var hubIcon = L.divIcon({ className: 'hub-icon', html: '<div class="hub-pin">📡</div>', iconSize: [30, 30], iconAnchor: [15, 15] });
        var hubBox = document.createElement('div');
        var hb = document.createElement('b'); hb.textContent = setting.hub_label || 'Titik Pusat / OLT';
        hubBox.appendChild(hb);
        L.marker([hub.lat, hub.lng], { icon: hubIcon, zIndexOffset: 1000 }).bindPopup(hubBox).addTo(map);
    }

    // ---------- ODP + jalur kabel ----------
    function hashOf(o) {
        return [hub.lat.toFixed(6), hub.lng.toFixed(6), parseFloat(o.latitude).toFixed(6), parseFloat(o.longitude).toFixed(6)].join('|');
    }

    function drawCable(path) {
        // Garis dasar tebal transparan + garis tipis terang beranimasi (efek aliran).
        L.polyline(path, { color: '#f1b44c', weight: 5, opacity: 0.25 }).addTo(map);
        L.polyline(path, { color: '#f1b44c', weight: 3, opacity: 0.95, dashArray: '6 12', className: 'fiber-flow' }).addTo(map);
    }

    function saveCable(odpId, path, hash) {
        fetch(storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ odp_id: odpId, path: path, src_hash: hash })
        }).catch(function () {});
    }

    var status = document.getElementById('routeStatus');
    var routeQueue = [];

    function routeOne(o) {
        return new Promise(function (resolve) {
            var url = 'https://router.project-osrm.org/route/v1/driving/'
                + hub.lng + ',' + hub.lat + ';' + parseFloat(o.longitude) + ',' + parseFloat(o.latitude)
                + '?overview=full&geometries=geojson';
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var path;
                    if (d && d.routes && d.routes.length) {
                        path = d.routes[0].geometry.coordinates.map(function (c) { return [c[1], c[0]]; });
                    } else {
                        path = [[hub.lat, hub.lng], [parseFloat(o.latitude), parseFloat(o.longitude)]];
                    }
                    drawCable(path);
                    saveCable(o.id, path, hashOf(o));
                    resolve();
                })
                .catch(function () {
                    // Gagal routing -> tarik garis lurus sebagai cadangan (tetap beranimasi).
                    drawCable([[hub.lat, hub.lng], [parseFloat(o.latitude), parseFloat(o.longitude)]]);
                    resolve();
                });
        });
    }

    // Proses antrean routing berurutan dengan jeda (hormati batas wajar OSRM publik).
    function processQueue() {
        if (! routeQueue.length) { if (status) status.textContent = ''; return; }
        var o = routeQueue.shift();
        if (status) status.textContent = '⏳ Menarik jalur kabel mengikuti jalan... (' + routeQueue.length + ' tersisa)';
        routeOne(o).then(function () { setTimeout(processQueue, 650); });
    }

    var bounds = [];
    if (hub) bounds.push([hub.lat, hub.lng]);

    odps.forEach(function (o) {
        var lat = parseFloat(o.latitude), lng = parseFloat(o.longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        bounds.push([lat, lng]);

        var box = document.createElement('div');
        var t = document.createElement('b'); t.textContent = o.nama || '(ODP)'; box.appendChild(t);
        if (o.kode) { box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode('Kode: ' + o.kode)); }
        box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode('Port: ' + (o.port || '-')));
        L.circleMarker([lat, lng], { radius: 7, color: '#556ee6', fillColor: '#556ee6', fillOpacity: 0.85, weight: 2 }).bindPopup(box).addTo(map);

        if (! hub) return;
        var cached = cables[o.id];
        if (cached && cached.src_hash === hashOf(o) && Array.isArray(cached.path)) {
            drawCable(cached.path); // pakai cache -> instan, tanpa OSRM
        } else {
            routeQueue.push(o); // perlu routing OSRM
        }
    });

    processQueue();
})();
</script>
@endsection
