@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <form method="GET" class="mb-3"><button type="submit" name="show_data" value="1" class="btn btn-primary">Tampilkan Data</button></form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>
                        <p class="text-muted">Menampilkan lokasi pelanggan aktif pada peta.</p>
                        <div id="map" style="height:500px;width:100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var map = L.map('map').setView([-0.12, 109.15], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

var customers = @json($customers);
var odps = @json($odps);

function textPopup(lines) {
    var popup = document.createElement('div');
    lines.forEach(function (line, index) {
        if (index > 0) {
            popup.appendChild(document.createElement('br'));
        }
        popup.appendChild(document.createTextNode(line));
    });

    return popup;
}

odps.forEach(function(odp) {
    var lat = parseFloat(odp.latitude);
    var lng = parseFloat(odp.longitude);
    if (!isNaN(lat) && !isNaN(lng)) {
        L.marker([lat, lng], {icon: L.divIcon({html:'ODP',className:'badge bg-info'})})
            .addTo(map).bindPopup(textPopup(['ODP: ' + (odp.nama ?? '')]));
    }
});

customers.forEach(function(c) {
    var lat = parseFloat(c.latitude);
    var lng = parseFloat(c.longitude);
    if (!isNaN(lat) && !isNaN(lng)) {
        L.marker([lat, lng])
            .addTo(map).bindPopup(textPopup([
                'ID: ' + (c.idpel ?? ''),
                'Nama: ' + (c.nama ?? ''),
                'Status: ' + (c.status ?? '')
            ]));
    }
});
</script>
@endsection
