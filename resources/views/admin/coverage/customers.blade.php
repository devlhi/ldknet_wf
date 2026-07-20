@extends('admin.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
<style>
    .customer-ont-icon { background: transparent; border: 0; }
    .customer-ont { position: relative; width: 48px; height: 48px; filter: drop-shadow(0 3px 4px rgba(0, 0, 0, .32)); }
    .customer-ont__wifi { position: absolute; top: -5px; left: 50%; color: #20c997; font-size: 25px; line-height: 1; transform: translateX(-50%); animation: customer-wifi-blink 1.25s ease-in-out infinite; text-shadow: 0 0 7px rgba(32, 201, 151, .9); }
    .customer-ont__unit { position: absolute; left: 7px; bottom: 3px; width: 34px; height: 23px; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; border-radius: 5px; background: #343a40; color: #fff; font-size: 9px; font-weight: 700; letter-spacing: .8px; }
    .customer-ont__led { position: absolute; right: 3px; bottom: 3px; width: 6px; height: 6px; border-radius: 50%; background: #20c997; box-shadow: 0 0 7px #20c997; animation: customer-led-blink .85s steps(2, start) infinite; }
    .customer-ont__pulse { position: absolute; left: 50%; bottom: 0; width: 38px; height: 38px; border: 2px solid rgba(32, 201, 151, .75); border-radius: 50%; transform: translate(-50%, 35%); animation: customer-ont-pulse 1.8s ease-out infinite; }
    @keyframes customer-wifi-blink { 0%, 100% { opacity: 1; transform: translateX(-50%) scale(1); } 50% { opacity: .35; transform: translateX(-50%) scale(.88); } }
    @keyframes customer-led-blink { 0%, 45% { opacity: 1; } 46%, 100% { opacity: .2; } }
    @keyframes customer-ont-pulse { 0% { opacity: .8; transform: translate(-50%, 35%) scale(.35); } 100% { opacity: 0; transform: translate(-50%, 35%) scale(1.35); } }
    @media (prefers-reduced-motion: reduce) { .customer-ont__wifi, .customer-ont__led, .customer-ont__pulse { animation: none; } }
</style>
@endsection

@section('content')
<div class="page-content"><div class="container-fluid"><div class="card"><div class="card-body">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h4 class="card-title mb-0">Peta Pelanggan</h4>
        <a href="{{ url('admin/coverage/rxpower') }}" class="btn btn-light"><i class="mdi mdi-signal me-1"></i> Cek Sinyal ONT</a>
    </div>
    <div class="d-flex gap-3 mb-3"><span class="badge bg-success">Terpetakan: {{ $mappedCount }}</span><span class="badge bg-secondary">Belum terpetakan: {{ $unmappedCount }}</span></div>
    <div id="map" style="height: 520px; width: 100%"></div>
</div></div></div></div>
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
(function () {
    var customers = @json($customers), odps = @json($odps), bounds = [];
    var map = L.map('map').setView([0.3, 109.5], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    function popup(lines) { var box = document.createElement('div'); lines.forEach(function (line, i) { if (i) box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode(line)); }); return box; }
    var customerOntIcon = L.divIcon({
        className: 'customer-ont-icon',
        html: '<div class="customer-ont"><span class="customer-ont__pulse"></span><i class="mdi mdi-wifi customer-ont__wifi"></i><span class="customer-ont__unit">ONT<span class="customer-ont__led"></span></span></div>',
        iconSize: [48, 48],
        iconAnchor: [24, 43],
        popupAnchor: [0, -39]
    });
    odps.forEach(function (odp) { var lat = Number(odp.latitude), lng = Number(odp.longitude); if (!Number.isFinite(lat) || !Number.isFinite(lng)) return; L.circleMarker([lat,lng], {radius:8,color:'#556ee6',fillOpacity:.8}).addTo(map).bindPopup(popup(['ODP: '+(odp.nama || '-'), 'Kode: '+(odp.kode || '-')])); bounds.push([lat,lng]); });
    customers.forEach(function (c) { var lat = parseFloat(c.latitude), lng = parseFloat(c.longitude); if (c.latitude == null || c.longitude == null || String(c.latitude).trim() === '' || String(c.longitude).trim() === '' || !Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) return; L.marker([lat,lng], {icon:customerOntIcon, riseOnHover:true}).addTo(map).bindPopup(popup(['ID: '+(c.idpel || '-'), 'Nama: '+(c.nama || '-'), 'ODP: '+(c.nama_odp || 'Belum ditetapkan'), 'Port ODP: '+(c.port_odp || '-')])); bounds.push([lat,lng]); });
    if (bounds.length === 1) map.setView(bounds[0], 15); else if (bounds.length > 1) map.fitBounds(bounds, {padding:[35,35]});
})();
</script>
@endsection
