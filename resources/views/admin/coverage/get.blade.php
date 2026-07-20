@extends('admin.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
@endsection

@section('content')
<div class="page-content"><div class="container-fluid"><div class="card"><div class="card-body">
    <h4 class="card-title">{{ $title }}</h4>
    <p class="text-muted">Klik peta, geser penanda, atau masukkan koordinat secara manual.</p>
    <div id="coordinateError" class="alert alert-danger d-none"></div>
    <div class="row mb-3">
        <div class="col-md-5"><label for="latitude" class="form-label">Latitude</label><input type="number" step="any" min="-90" max="90" id="latitude" class="form-control"></div>
        <div class="col-md-5"><label for="longitude" class="form-label">Longitude</label><input type="number" step="any" min="-180" max="180" id="longitude" class="form-control"></div>
        <div class="col-md-2 d-flex align-items-end"><button type="button" id="copyCoordinate" class="btn btn-primary w-100">Salin</button></div>
    </div>
    <div id="map" style="height: 520px"></div>
</div></div></div></div>
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
(function () {
    var center = @json($mapCenter), latInput = document.getElementById('latitude'), lngInput = document.getElementById('longitude'), error = document.getElementById('coordinateError');
    var centerLat = Number(center.latitude), centerLng = Number(center.longitude);
    var initial = Number.isFinite(centerLat) && Number.isFinite(centerLng) && centerLat >= -90 && centerLat <= 90 && centerLng >= -180 && centerLng <= 180 ? [centerLat, centerLng] : [0.3, 109.5];
    var map = L.map('map').setView(initial, Number(center.zoom) || 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
    var marker = L.marker(initial, {draggable:true}).addTo(map);
    function valid(lat, lng) { return Number.isFinite(lat) && Number.isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180; }
    function setPoint(lat, lng, move) { if (!valid(lat,lng)) { error.textContent='Koordinat tidak valid. Latitude -90 sampai 90 dan longitude -180 sampai 180.'; error.classList.remove('d-none'); return false; } error.classList.add('d-none'); marker.setLatLng([lat,lng]); latInput.value=lat.toFixed(7); lngInput.value=lng.toFixed(7); if(move) map.panTo([lat,lng]); return true; }
    setPoint(initial[0], initial[1], false);
    map.on('click', function(e){ setPoint(e.latlng.lat,e.latlng.lng,false); });
    marker.on('dragend', function(){ var p=marker.getLatLng(); setPoint(p.lat,p.lng,false); });
    function manual(){ setPoint(Number(latInput.value),Number(lngInput.value),true); }
    latInput.addEventListener('change',manual); lngInput.addEventListener('change',manual);
    document.getElementById('copyCoordinate').addEventListener('click', function(){ var lat=Number(latInput.value),lng=Number(lngInput.value); if(!valid(lat,lng)){ manual(); return; } var value=latInput.value+', '+lngInput.value; navigator.clipboard.writeText(value).then(function(){ document.getElementById('copyCoordinate').textContent='Tersalin'; setTimeout(function(){document.getElementById('copyCoordinate').textContent='Salin';},1200); }); });
})();
</script>
@endsection
