@extends('admin.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
@endsection

@section('content')
<div class="page-content"><div class="container-fluid">
    <div class="card"><div class="card-body">
        <h4 class="card-title">{{ $title }}</h4>
        <p class="text-muted">Data ODC dibaca langsung dari database.</p>
        <div id="map" style="height: 450px" class="mb-4"></div>
        <div class="table-responsive"><table class="table table-bordered align-middle">
            <thead><tr><th>No</th><th>Nama</th><th>OLT ID</th><th>Koordinat</th><th>Deskripsi</th></tr></thead>
            <tbody>@forelse($odcs as $i => $odc)<tr><td>{{ $i + 1 }}</td><td>{{ $odc->name }}</td><td>{{ $odc->olt_id ?: '-' }}</td><td>{{ $odc->latitude && $odc->longitude ? $odc->latitude.', '.$odc->longitude : 'Belum tersedia' }}</td><td>{{ $odc->description ?: '-' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">Belum ada data ODC</td></tr>@endforelse</tbody>
        </table></div>
    </div></div>
</div></div>
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
(function () {
    var data = @json($odcs);
    var map = L.map('map').setView([0.3, 109.5], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    var bounds = [];
    data.forEach(function (odc) {
        var lat = parseFloat(odc.latitude), lng = parseFloat(odc.longitude);
        if (odc.latitude == null || odc.longitude == null || String(odc.latitude).trim() === '' || String(odc.longitude).trim() === '' || !Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
        var box = document.createElement('div');
        var name = document.createElement('strong'); name.textContent = odc.name || 'ODC'; box.appendChild(name);
        if (odc.description) { box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode(odc.description)); }
        L.marker([lat, lng]).addTo(map).bindPopup(box); bounds.push([lat, lng]);
    });
    if (bounds.length === 1) map.setView(bounds[0], 15); else if (bounds.length > 1) map.fitBounds(bounds, {padding: [30, 30]});
})();
</script>
@endsection
