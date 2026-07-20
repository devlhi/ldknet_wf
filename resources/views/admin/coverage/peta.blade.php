@extends('admin.layout')

@section('css')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <style>
        /* Animasi aliran cahaya di sepanjang kabel fiber (dash bergerak hub -> ODP) */
        .fiber-flow { animation: fiberdash 1.1s linear infinite; }
        @keyframes fiberdash { to { stroke-dashoffset: -24; } }
        .hub-pin { font-size: 24px; line-height: 1; filter: drop-shadow(0 1px 2px rgba(0,0,0,.4)); text-align: center; }
        .network-card-icon, .pole-icon { background: transparent; border: 0; }
        .network-card { position: relative; min-width: 90px; padding: 7px 8px 7px 34px; border: 2px solid #fff; border-radius: 9px; color: #fff; font-size: 9px; font-weight: 700; line-height: 1.15; white-space: nowrap; box-shadow: 0 4px 12px rgba(15,23,42,.3); }
        .network-card.odc { background: #0f766e; }
        .network-card.odp { background: #4f46e5; }
        .network-card svg { position: absolute; left: 7px; top: 50%; width: 20px; height: 20px; transform: translateY(-50%); fill: none; stroke: currentColor; stroke-width: 1.8; }
        .pole-svg { width: 16px; height: 28px; filter: drop-shadow(0 2px 2px rgba(0,0,0,.35)); }
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
                            <p class="text-muted mb-0">Titik pusat/OLT, ODC, ODP, tiang visual, dan jalur kabel fiber beranimasi.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ url('admin/coverage/odc') }}" class="btn btn-light"><i class="mdi mdi-access-point-network me-1"></i> Data ODC</a>
                            <a href="{{ url('admin/coverage/area') }}" class="btn btn-light"><i class="mdi mdi-map-marker-radius me-1"></i> Data Area</a>
                            <a href="{{ url('admin/coverage/peta/pengaturan') }}" class="btn btn-light"><i class="mdi mdi-cog me-1"></i> Pengaturan Peta</a>
                        </div>
                    </div>
                </div>
            </div>

            @if (! is_numeric($setting->hub_lat) || ! is_numeric($setting->hub_lng))
                <div class="alert alert-warning">
                    <i class="mdi mdi-information-outline me-1"></i> Titik pusat/OLT belum diatur. Jalur ODC ke ODP tetap dapat ditampilkan.
                    <a href="{{ url('admin/coverage/peta/pengaturan') }}" class="alert-link">Atur titik pusat di sini</a>.
                </div>
            @endif
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 mb-2 small text-muted">
                        <span><i class="mdi mdi-access-point-network text-danger"></i> Titik Pusat / OLT</span>
                        <span class="text-success">ODC</span>
                        <span class="text-primary">ODP</span>
                        <span class="text-warning">Tiang</span>
                        <span><i class="mdi mdi-minus" style="color:#f1b44c"></i> Jalur ODC ke ODP</span>
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
    var odcs = @json($odcs);
    var odcAssignments = @json($odcAssignments);
    var cables = @json($cables);
    var storeUrl = "{{ url('admin/coverage/peta/cable') }}";
    var csrf = "{{ csrf_token() }}";

    var hubLat = parseFloat(setting.hub_lat);
    var hubLng = parseFloat(setting.hub_lng);
    var hub = Number.isFinite(hubLat) && Number.isFinite(hubLng)
        ? { lat: hubLat, lng: hubLng }
        : null;

    // ---------- Basemap (semua gratis tanpa API key) ----------
    function tl(url, opts) { return L.tileLayer(url, Object.assign({ maxZoom: 19 }, opts || {})); }
    var basemaps = {
        'Jalan': tl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }),
        'Satelit': tl('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri' }),
        'Topografi': tl('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenTopoMap', maxZoom: 17 }),
        'Gelap': tl('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', { attribution: '&copy; CARTO' })
    };
    var defaultName = { streets: 'Jalan', satelit: 'Satelit', topografi: 'Topografi', gelap: 'Gelap' }[setting.basemap] || 'Jalan';

    var centerLat = parseFloat(setting.center_lat);
    var centerLng = parseFloat(setting.center_lng);
    var map = L.map('map', {
        center: [Number.isFinite(centerLat) ? centerLat : 0.3, Number.isFinite(centerLng) ? centerLng : 109.5],
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

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]; });
    }
    function networkIcon(type, label) {
        var path = type === 'odc' ? 'M4 5h16v12H4zM8 21h8M12 17v4M8 9h8M8 13h5' : 'M5 7h14v10H5zM8 11h2M12 11h2M16 11h1M8 15h9';
        return L.divIcon({className:'network-card-icon', html:'<div class="network-card '+type+'"><svg viewBox="0 0 24 24"><path d="'+path+'"/></svg><span>'+type.toUpperCase()+'<br>'+escapeHtml(label)+'</span></div>', iconSize:[105,40], iconAnchor:[52,20]});
    }
    var poleIcon = L.divIcon({className:'pole-icon', html:'<svg class="pole-svg" viewBox="0 0 18 32"><path d="M8 3h2v27H8zM3 7h12v2H3zM5 12h8v2H5z" fill="#7c4a24"/><circle cx="4" cy="8" r="1.5" fill="#d1d5db"/><circle cx="14" cy="8" r="1.5" fill="#d1d5db"/></svg>', iconSize:[18,32], iconAnchor:[9,30]});
    var odcById = {};
    odcs.forEach(function (odc) { odcById[String(odc.id)] = odc; });

    // ---------- ODC, ODP + jalur kabel ----------
    function hashOf(o) {
        var odc = odcById[String(odcAssignments[String(o.id)] || '')];
        if (!odc) return null;
        var lat = parseFloat(o.latitude), lng = parseFloat(o.longitude), odcLat = parseFloat(odc.latitude), odcLng = parseFloat(odc.longitude);
        if (![lat,lng,odcLat,odcLng].every(Number.isFinite)) return null;
        return [Number(odc.id).toFixed(6), odcLat.toFixed(6), odcLng.toFixed(6), lat.toFixed(6), lng.toFixed(6)].join('|');
    }

    function drawCable(path) {
        // Garis dasar tebal transparan + garis tipis terang beranimasi (efek aliran).
        L.polyline(path, { color: '#f1b44c', weight: 5, opacity: 0.25 }).addTo(map);
        L.polyline(path, { color: '#f1b44c', weight: 3, opacity: 0.95, dashArray: '6 12', className: 'fiber-flow' }).addTo(map);
        var step = Math.max(12, Math.floor(path.length / 5));
        for (var i = step; i < path.length - 1; i += step) {
            L.marker(path[i], {icon:poleIcon, interactive:false, zIndexOffset:300}).addTo(map);
        }
    }

    var status = document.getElementById('routeStatus');
    var routeQueue = [];
    var routingFailures = 0;
    var cacheFailures = 0;

    function updateStatus(active) {
        if (!status) return;
        var parts = [];
        if (active) parts.push('⏳ Menarik jalur kabel mengikuti jalan... (' + routeQueue.length + ' tersisa)');
        if (routingFailures) parts.push('Routing gagal: ' + routingFailures + ' (garis lurus digunakan)');
        if (cacheFailures) parts.push('Cache gagal: ' + cacheFailures);
        status.textContent = parts.join(' · ');
        status.className = (routingFailures || cacheFailures) ? 'text-warning' : 'text-info';
    }

    function saveCable(odpId, path, hash) {
        if (!hash) return Promise.resolve(false);
        return fetch(storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ odp_id: odpId, path: path, src_hash: hash })
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return true;
        }).catch(function () {
            cacheFailures++;
            updateStatus(routeQueue.length > 0);
            return false;
        });
    }

    function routeOne(o) {
        var odc = odcById[String(odcAssignments[String(o.id)] || '')];
        if (!odc) return Promise.resolve(false);
        var odcLat = parseFloat(odc.latitude), odcLng = parseFloat(odc.longitude);
        var fallback = [[odcLat, odcLng], [parseFloat(o.latitude), parseFloat(o.longitude)]];
        var hash = hashOf(o);
        var url = 'https://router.project-osrm.org/route/v1/driving/'
            + odcLng + ',' + odcLat + ';' + parseFloat(o.longitude) + ',' + parseFloat(o.latitude)
            + '?overview=simplified&geometries=geojson';

        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (d) {
                if (!d || !d.routes || !d.routes.length || !d.routes[0].geometry || !Array.isArray(d.routes[0].geometry.coordinates)) {
                    throw new Error('Rute tidak tersedia');
                }
                return d.routes[0].geometry.coordinates.map(function (c) { return [c[1], c[0]]; });
            })
            .catch(function () {
                // Cache fallback juga disimpan agar reload tidak terus memanggil OSRM yang sedang gagal.
                routingFailures++;
                updateStatus(routeQueue.length > 0);
                return fallback;
            })
            .then(function (path) {
                drawCable(path);
                return saveCable(o.id, path, hash);
            });
    }

    // Proses antrean routing berurutan dengan jeda (hormati batas wajar OSRM publik).
    function processQueue() {
        if (!routeQueue.length) { updateStatus(false); return; }
        var o = routeQueue.shift();
        updateStatus(true);
        routeOne(o).then(function () { setTimeout(processQueue, 650); });
    }

    var bounds = [];
    if (hub) bounds.push([hub.lat, hub.lng]);
    odcs.forEach(function (odc) {
        var lat = parseFloat(odc.latitude), lng = parseFloat(odc.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        var box = document.createElement('div'); var title = document.createElement('b'); title.textContent = odc.name || 'ODC'; box.appendChild(title); if (odc.code) { box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode('Kode: '+odc.code)); }
        L.marker([lat,lng], {icon:networkIcon('odc', odc.name), zIndexOffset:700}).bindPopup(box).addTo(map);
        bounds.push([lat,lng]);
        if (hub) L.polyline([[hub.lat,hub.lng],[lat,lng]], {color:'#14b8a6',weight:2,opacity:.55,dashArray:'4 8'}).addTo(map);
    });

    odps.forEach(function (o) {
        var lat = parseFloat(o.latitude), lng = parseFloat(o.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        bounds.push([lat, lng]);

        var box = document.createElement('div');
        var t = document.createElement('b'); t.textContent = o.nama || '(ODP)'; box.appendChild(t);
        if (o.kode) { box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode('Kode: ' + o.kode)); }
        box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode('Port: ' + (o.port || '-')));
        L.marker([lat, lng], {icon:networkIcon('odp', o.nama), zIndexOffset:600}).bindPopup(box).addTo(map);

        if (!odcAssignments[String(o.id)]) return;
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
