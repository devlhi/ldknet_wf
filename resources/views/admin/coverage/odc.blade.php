@extends('admin.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
<style>
    .network-card-icon { background: transparent; border: 0; }
    .network-card { position: relative; min-width: 96px; padding: 7px 9px 7px 36px; border: 2px solid #fff; border-radius: 9px; background: #0f766e; color: #fff; font-size: 10px; font-weight: 700; line-height: 1.15; box-shadow: 0 4px 12px rgba(15, 118, 110, .35); white-space: nowrap; }
    .network-card svg { position: absolute; left: 8px; top: 50%; width: 21px; height: 21px; transform: translateY(-50%); fill: none; stroke: currentColor; stroke-width: 1.8; }
    #map { height: 450px; border-radius: 6px; }
</style>
@endsection

@section('content')
<div class="page-content"><div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div><h4 class="mb-0">Data ODC</h4><p class="text-muted mb-0">ODC wajib dibuat sebelum menambahkan ODP.</p></div>
        <button type="button" class="btn btn-success" id="btnAddOdc"><i class="mdi mdi-plus me-1"></i> Tambah ODC</button>
    </div>
    @if (session('auth_errors'))<div class="alert alert-danger">@foreach(session('auth_errors') as $error)<p class="mb-0">{{ $error }}</p>@endforeach</div>@endif
    @if (session('success'))<div class="alert alert-success">{{ implode(' ', session('success')) }}</div>@endif
    <div class="card"><div class="card-body">
        <div id="map" class="mb-4"></div>
        <div class="table-responsive"><table class="table table-bordered align-middle">
            <thead><tr><th>No</th><th>Nama</th><th>Kode</th><th>ODP</th><th>Koordinat</th><th>Deskripsi</th><th>Aksi</th></tr></thead>
            <tbody>@forelse($odcs as $i => $odc)<tr>
                <td>{{ $i + 1 }}</td><td>{{ $odc->name }}</td><td>{{ $odc->code ?: '-' }}</td><td>{{ $odc->odp_count }}</td>
                <td>{{ $odc->latitude.', '.$odc->longitude }}</td><td>{{ $odc->description ?: '-' }}</td>
                <td class="text-nowrap">
                    <button type="button" class="btn btn-sm btn-primary btn-edit-odc" data-id="{{ $odc->id }}" data-name="{{ $odc->name }}" data-code="{{ $odc->code }}" data-lat="{{ $odc->latitude }}" data-lng="{{ $odc->longitude }}" data-description="{{ $odc->description }}"><i class="mdi mdi-pencil"></i></button>
                    <form action="{{ url('admin/coverage/odc/delete/'.$odc->id) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-delete"></i></button></form>
                </td>
            </tr>@empty<tr><td colspan="7" class="text-center text-muted">Belum ada data ODC. Tambahkan ODC sebelum membuat ODP.</td></tr>@endforelse</tbody>
        </table></div>
    </div></div>
</div></div>

<div class="modal fade" id="odcModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form id="odcForm" method="POST" action="{{ url('admin/coverage/odc/add') }}">@csrf
        <div class="modal-header"><h6 class="modal-title" id="odcModalTitle">Tambah ODC</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row">
            <div class="col-md-8 mb-3"><label class="form-label">Nama ODC</label><input type="text" class="form-control" name="name" id="odcName" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Kode</label><input type="text" class="form-control" name="code" id="odcCode"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Latitude</label><input type="text" class="form-control" name="latitude" id="odcLat" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Longitude</label><input type="text" class="form-control" name="longitude" id="odcLng" required></div>
            <div class="col-12"><label class="form-label">Deskripsi</label><textarea class="form-control" name="description" id="odcDescription" rows="3"></textarea></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
    </form>
</div></div></div>
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
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        var label = String(odc.name || 'ODC').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]; });
        var icon = L.divIcon({className:'network-card-icon', html:'<div class="network-card"><svg viewBox="0 0 24 24"><path d="M4 5h16v12H4zM8 21h8M12 17v4M8 9h8M8 13h5"/></svg><span>ODC<br>'+label+'</span></div>', iconSize:[110,42], iconAnchor:[55,21]});
        var box = document.createElement('div'); var name = document.createElement('strong'); name.textContent = odc.name || 'ODC'; box.appendChild(name); box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode('ODP: '+odc.odp_count));
        L.marker([lat, lng], {icon:icon}).addTo(map).bindPopup(box); bounds.push([lat, lng]);
    });
    if (bounds.length === 1) map.setView(bounds[0], 15); else if (bounds.length > 1) map.fitBounds(bounds, {padding:[60,60]});

    var modal = new bootstrap.Modal(document.getElementById('odcModal'));
    var form = document.getElementById('odcForm'), addUrl = @json(url('admin/coverage/odc/add')), updateBase = @json(url('admin/coverage/odc/update'));
    function openForm(data) {
        form.reset(); form.action = data ? updateBase+'/'+data.id : addUrl;
        document.getElementById('odcModalTitle').textContent = data ? 'Edit ODC' : 'Tambah ODC';
        if (data) { document.getElementById('odcName').value=data.name||''; document.getElementById('odcCode').value=data.code||''; document.getElementById('odcLat').value=data.lat||''; document.getElementById('odcLng').value=data.lng||''; document.getElementById('odcDescription').value=data.description||''; }
        modal.show();
    }
    document.getElementById('btnAddOdc').addEventListener('click', function(){ openForm(null); });
    document.addEventListener('click', function(e){ var b=e.target.closest('.btn-edit-odc'); if(!b)return; openForm(b.dataset); });
})();
</script>
@endsection
