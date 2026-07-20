@extends('admin.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
@endsection

@section('content')
<div class="page-content"><div class="container-fluid"><div class="card"><div class="card-body">
    <h4 class="card-title">{{ $title }}</h4>
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
    odps.forEach(function (odp) { var lat = Number(odp.latitude), lng = Number(odp.longitude); if (!Number.isFinite(lat) || !Number.isFinite(lng)) return; L.circleMarker([lat,lng], {radius:8,color:'#556ee6',fillOpacity:.8}).addTo(map).bindPopup(popup(['ODP: '+(odp.nama || '-'), 'Kode: '+(odp.kode || '-')])); bounds.push([lat,lng]); });
    customers.forEach(function (c) { var lat = parseFloat(c.latitude), lng = parseFloat(c.longitude); if (c.latitude == null || c.longitude == null || String(c.latitude).trim() === '' || String(c.longitude).trim() === '' || !Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) return; L.marker([lat,lng]).addTo(map).bindPopup(popup(['ID: '+(c.idpel || '-'), 'Nama: '+(c.nama || '-'), 'ODP: '+(c.nama_odp || 'Belum ditetapkan'), 'Port ODP: '+(c.port_odp || '-')])); bounds.push([lat,lng]); });
    if (bounds.length === 1) map.setView(bounds[0], 15); else if (bounds.length > 1) map.fitBounds(bounds, {padding:[35,35]});
})();
</script>
@endsection
