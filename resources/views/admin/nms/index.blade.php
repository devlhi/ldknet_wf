@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @if (session('auth_errors'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        @foreach (session('auth_errors') as $err)
                            {{ $err }}<br>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle me-2"></i>
                        @foreach (session('success') as $suc)
                            {{ $suc }}<br>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-map me-1"></i> Peta Device NMS</h4>
                        <div>
                            <button type="button" id="btn-fiber-line" class="btn btn-outline-success btn-sm me-2">
                                <i class="uil uil-link-h"></i> Tarik Fiber Line
                            </button>
                            <a href="{{ $monitorUrl }}" target="_blank" class="btn btn-success btn-sm me-2">
                                <i class="uil uil-external-link-alt"></i> Public Monitor
                            </a>
                            <a href="{{ url('admin/nms/sla') }}" class="btn btn-info btn-sm me-2 text-white">
                                <i class="uil uil-file-chart-alt"></i> SLA Report
                            </a>
                            <a href="{{ url('admin/nms/device/add') }}" class="btn btn-primary btn-sm">
                                <i class="uil uil-plus"></i> Tambah Device
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="nms-map" style="height:450px;width:100%;border-radius:8px;"></div>
                        <p class="text-muted mt-2 mb-0"><i class="uil uil-info-circle"></i> Klik marker untuk info device, SFP power, dan link status. Marker blinking = online, redup = offline. Warna ikon sesuai tipe perangkat.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <ul class="nav nav-tabs card-header-tabs mb-0" id="nmsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-device-btn" data-bs-toggle="tab" data-bs-target="#tab-device" type="button" role="tab">
                                    <i class="uil uil-server me-1"></i> Daftar Device
                                    <span class="badge bg-secondary ms-1">{{ $devices->count() }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-link-btn" data-bs-toggle="tab" data-bs-target="#tab-link" type="button" role="tab">
                                    <i class="uil uil-link me-1"></i> Link / Koneksi Antar Device
                                    <span class="badge bg-secondary ms-1">{{ $links->count() }}</span>
                                </button>
                            </li>
                        </ul>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#linkModal">
                            <i class="uil uil-plus"></i> Tambah Link
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tab-device" role="tabpanel">
                                @if ($devices->isEmpty())
                                    <p class="text-muted text-center py-4">Belum ada device. Klik "Tambah Device" untuk mulai.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="datatable">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama</th>
                                                    <th>Tipe</th>
                                                    <th>IP</th>
                                                    <th>Port</th>
                                                    <th>Lokasi</th>
                                                    <th>Status</th>
                                                    <th>Konektivitas</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($devices as $i => $device)
                                                    <tr>
                                                        <td>{{ $i + 1 }}</td>
                                                        <td>{{ $device->nama }}</td>
                                                        <td>
                                                            @if ($device->tipe === 'mikrotik')
                                                                <span class="badge bg-primary">Mikrotik</span>
                                                            @elseif ($device->tipe === 'crs')
                                                                <span class="badge bg-info">CRS Switch</span>
                                                            @elseif ($device->tipe === 'olt')
                                                                <span class="badge bg-warning">OLT</span>
                                                            @else
                                                                <span class="badge bg-secondary">SNMP</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $device->ip }}</td>
                                                        <td>{{ $device->port }}</td>
                                                        <td>{{ $device->lokasi ?: '-' }}</td>
                                                        <td>
                                                            @if ($device->status === 'active')
                                                                <span class="badge bg-success">ACTIVE</span>
                                                            @else
                                                                <span class="badge bg-secondary">INACTIVE</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary" id="conn-status-{{ $device->id }}">
                                                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ url('admin/nms/device/detail/'.$device->id) }}" class="btn btn-sm btn-info text-white">
                                                                <i class="uil uil-eye"></i>
                                                            </a>
                                                            <a href="{{ url('admin/nms/device/edit/'.$device->id) }}" class="btn btn-sm btn-warning">
                                                                <i class="uil uil-edit"></i>
                                                            </a>
                                                            <a href="{{ url('admin/nms/device/delete/'.$device->id) }}" class="btn btn-sm btn-danger swal-delete" data-text="Hapus device {{ $device->nama }}?">
                                                                <i class="uil uil-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>

                            <div class="tab-pane fade" id="tab-link" role="tabpanel">
                                @if ($links->isEmpty())
                                    <p class="text-muted text-center py-4">Belum ada link. Klik "Tambah Link" untuk menghubungkan device di map.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Device A</th>
                                                    <th>Port A</th>
                                                    <th>Device B</th>
                                                    <th>Port B</th>
                                                    <th>Tipe</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($links as $i => $link)
                                                    <tr>
                                                        <td>{{ $i + 1 }}</td>
                                                        <td>{{ $link->deviceA->nama ?? '-' }}</td>
                                                        <td>{{ $link->port_a ?: '-' }}</td>
                                                        <td>{{ $link->deviceB->nama ?? '-' }}</td>
                                                        <td>{{ $link->port_b ?: '-' }}</td>
                                                        <td><span class="badge bg-info">{{ strtoupper($link->link_type) }}</span></td>
                                                        <td>
                                                            @if ($link->status === 'active')
                                                                <span class="badge bg-success">ACTIVE</span>
                                                            @else
                                                                <span class="badge bg-secondary">INACTIVE</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ url('admin/nms/link/delete/'.$link->id) }}" class="btn btn-sm btn-danger swal-delete" data-text="Hapus link ini?">
                                                                <i class="uil uil-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Link Modal -->
<div class="modal fade" id="linkModal" tabindex="-1" aria-labelledby="linkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ url('admin/nms/link/add') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="linkModalLabel"><i class="uil uil-link me-1"></i> Tambah Link Fiber</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Device A</label>
                        <select name="device_a_id" id="device_a_id" class="form-select" required>
                            <option value="">-- Pilih Device A --</option>
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->nama }} ({{ $device->ip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            Port A (opsional)
                            <span id="port_a_loading" class="text-muted d-none"><span class="spinner-border spinner-border-sm"></span> mengambil interface...</span>
                        </label>
                        <input type="text" name="port_a" id="port_a" class="form-control" list="port_a_list" placeholder="pilih device dulu, lalu pilih interface">
                        <datalist id="port_a_list"></datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device B</label>
                        <select name="device_b_id" id="device_b_id" class="form-select" required>
                            <option value="">-- Pilih Device B --</option>
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->nama }} ({{ $device->ip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            Port B (opsional)
                            <span id="port_b_loading" class="text-muted d-none"><span class="spinner-border spinner-border-sm"></span> mengambil interface...</span>
                        </label>
                        <input type="text" name="port_b" id="port_b" class="form-control" list="port_b_list" placeholder="pilih device dulu, lalu pilih interface">
                        <datalist id="port_b_list"></datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Link</label>
                        <select name="link_type" class="form-select" required>
                            <option value="fiber">Fiber</option>
                            <option value="wireless">Wireless</option>
                            <option value="copper">Copper</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
<style>
.nms-device-icon.nms-marker-online {
    border-color: #34c759;
}
.nms-device-icon.nms-marker-offline {
    opacity: 0.55;
    filter: grayscale(1);
}
.nms-device-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,.5);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
}
.nms-device-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.nms-device-label {
    background: rgba(255,255,255,.95);
    border: 1px solid rgba(0,0,0,.12);
    border-radius: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,.18);
    color: #2f3640;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
}
.nms-device-label::before {
    display: none;
}
.nms-lbl-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    margin-right: 5px;
}
.nms-lbl-dot.up { background: #34c759; }
.nms-lbl-dot.down { background: #f46a6a; }
.nms-lbl-dot.wait { background: #74788d; }
.nms-pulse-ring {
    position: absolute;
    top: -3px;
    left: -3px;
    width: 46px;
    height: 46px;
    border-radius: 8px;
    border: 2px solid #34c759;
}
.nms-sfp-table {
    font-size: 11px;
    border-collapse: collapse;
    width: 100%;
    margin-top: 4px;
}
.nms-sfp-table th {
    padding: 3px 6px;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    text-align: center;
}
.nms-sfp-table td {
    padding: 3px 6px;
    border: 1px solid #dee2e6;
    text-align: center;
}
.nms-sfp-pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 4px;
    font-size: 10px;
    color: #74788d;
}
.nms-sfp-page-ind {
    background: #f1f3f5;
    border-radius: 10px;
    padding: 1px 8px;
    font-weight: 600;
}
.nms-popup-online {
    border-left: 4px solid #34c759;
}
.nms-popup-offline {
    border-left: 4px solid #f46a6a;
    opacity: 0.85;
}
.leaflet-popup-content {
    margin: 8px 12px;
}
.leaflet-popup-content-wrapper {
    border-radius: 8px;
}
@keyframes nmsLineFlow {
    from { stroke-dashoffset: 24; }
    to { stroke-dashoffset: 0; }
}
.leaflet-overlay-pane path.nms-flow-dash {
    stroke-dasharray: 10 14;
    animation: nmsLineFlow 1.1s linear infinite;
}
.leaflet-overlay-pane path.nms-flow-wireless {
    stroke-dasharray: 3 12;
    animation: nmsLineFlow 1.4s linear infinite;
}
@media (prefers-reduced-motion: reduce) {
    .leaflet-overlay-pane path.nms-flow-dash,
    .leaflet-overlay-pane path.nms-flow-wireless {
        animation: none;
    }
}
</style>
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[char]));
}

function sfpBadge(val) {
    if (val === null || val === undefined || val === '') return '<span class="badge bg-secondary">-</span>';
    var v = parseFloat(val);
    if (isNaN(v)) return '<span class="badge bg-secondary">' + escapeHtml(val) + '</span>';
    var cls = 'success';
    if (v <= -28) cls = 'danger';
    else if (v <= -25) cls = 'warning';
    return '<span class="badge bg-' + cls + '">' + v.toFixed(2) + ' dBm</span>';
}

var deviceIconUrls = {
    mikrotik: '{{ asset("assets/images/nms/mikrotik.svg") }}',
    crs: '{{ asset("assets/images/nms/switch.svg") }}',
    olt: '{{ asset("assets/images/nms/olt.svg") }}',
    snmp: '{{ asset("assets/images/nms/snmp.svg") }}'
};

var deviceColors = {
    mikrotik: '#5b73e8',
    crs: '#50a5f1',
    olt: '#f1b44c',
    snmp: '#74788d'
};

// Pilih ikon sesuai vendor. Perangkat vendor Mikrotik (RouterOS: Mikrotik,
// CRS/CCR switch, RouterBoard) selalu pakai ikon Mikrotik walau tipe berbeda.
function iconForDevice(d) {
    var tipe = String(d.tipe || '').toLowerCase();
    var hay = (String(d.nama || '') + ' ' + String(d.vendor || '') + ' ' + String(d.routerboard_model || '')).toLowerCase();
    if (tipe === 'mikrotik' || tipe === 'crs' || /mikrotik|routeros|routerboard|\bcrs\b|\bccr\b|\brb\d/.test(hay)) {
        return deviceIconUrls.mikrotik;
    }
    return deviceIconUrls[tipe] || deviceIconUrls.snmp;
}

var map = L.map('nms-map').setView([-0.12, 109.15], 11);

var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
});

var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: '&copy; Esri, Maxar, Earthstar Geographics',
    maxZoom: 19
});

var terrainLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenTopoMap (CC-BY-SA)',
    maxZoom: 17
});

var streetsLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap, &copy; CARTO',
    maxZoom: 19
});

var topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenTopoMap (CC-BY-SA)',
    maxZoom: 17
});

osmLayer.addTo(map);

L.control.layers({
    'OpenStreetMap': osmLayer,
    'Satelit (Esri)': satelliteLayer,
    'Terrain (Topo)': terrainLayer,
    'Light (Carto)': streetsLayer
}, null, {
    position: 'bottomright',
    collapsed: false
}).addTo(map);

var onlineDevices = {};

function sfpHasPower(p) {
    return (p.rx_power !== null && p.rx_power !== undefined && p.rx_power !== '') ||
           (p.tx_power !== null && p.tx_power !== undefined && p.tx_power !== '');
}

function sfpTableBlock(ports) {
    var t = '<table class="nms-sfp-table" style="font-size:11px; margin-top:5px; width:100%;">';
    t += '<thead><tr><th>Port</th><th>RX</th><th>TX</th></tr></thead><tbody>';
    ports.forEach(function(p) {
        t += '<tr><td>' + escapeHtml(p.port_name) + '</td><td>' + sfpBadge(p.rx_power) + '</td><td>' + sfpBadge(p.tx_power) + '</td></tr>';
    });
    t += '</tbody></table>';
    return t;
}

function buildSfpTableHtml(ports) {
    var valid = (ports || []).filter(sfpHasPower);
    if (valid.length === 0) {
        return '<small style="color:#aaa;">Tidak ada modul SFP dengan power</small><br>';
    }
    var pageSize = 8;
    if (valid.length <= pageSize) {
        return sfpTableBlock(valid);
    }
    var pages = [];
    for (var i = 0; i < valid.length; i += pageSize) {
        pages.push(valid.slice(i, i + pageSize));
    }
    var out = '<div class="nms-sfp-paged" data-page="0">';
    pages.forEach(function(pg, idx) {
        out += '<div class="nms-sfp-page" style="' + (idx === 0 ? '' : 'display:none;') + '">' + sfpTableBlock(pg) + '</div>';
    });
    out += '</div>';
    out += '<div class="nms-sfp-pager"><span class="nms-sfp-page-ind">1 / ' + pages.length + '</span> <small>(auto bergantian)</small></div>';
    return out;
}

function rotateSfpPages() {
    document.querySelectorAll('.nms-sfp-paged').forEach(function(container) {
        var pages = container.querySelectorAll('.nms-sfp-page');
        if (pages.length < 2) return;
        var cur = parseInt(container.getAttribute('data-page') || '0', 10);
        pages[cur].style.display = 'none';
        cur = (cur + 1) % pages.length;
        pages[cur].style.display = '';
        container.setAttribute('data-page', String(cur));
        var ind = container.parentNode.querySelector('.nms-sfp-page-ind');
        if (ind) ind.textContent = (cur + 1) + ' / ' + pages.length;
    });
}
setInterval(rotateSfpPages, 3500);

function buildPopupHtml(d, isOnline) {
    var iconUrl = iconForDevice(d);
    var color = deviceColors[d.tipe] || '#5b73e8';
    var popupClass = isOnline ? 'nms-popup-online' : 'nms-popup-offline';
    var statusBadge = isOnline
        ? '<span class="badge bg-success">ONLINE</span>'
        : '<span class="badge bg-danger">OFFLINE</span>';

    var html = '<div class="' + popupClass + '" style="min-width:220px;padding-left:8px;">';
    html += '<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">';
    html += '<img src="' + iconUrl + '" width="32" height="32" style="border-radius:6px; object-fit:contain;">';
    html += '<div><strong>' + escapeHtml(d.nama) + '</strong> ' + statusBadge + '<br>';
    html += '<small style="color:#888;">' + escapeHtml(d.tipe.toUpperCase()) + ' | ' + escapeHtml(d.ip) + '</small></div>';
    html += '</div>';

    html += buildSfpTableHtml(d.sfp_ports);

    html += '<a href="{{ url("admin/nms/device/detail") }}/' + d.id + '" class="btn btn-sm btn-info text-white mt-2" style="font-size:11px;"><i class="uil uil-eye"></i> Detail</a>';
    html += '</div>';

    return html;
}

function renderMarker(d) {
    var lat = parseFloat(d.latitude);
    var lng = parseFloat(d.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    var isOnline = onlineDevices[d.id] === 'up';
    var iconUrl = iconForDevice(d);
    var blinkClass = isOnline ? 'nms-marker-online' : 'nms-marker-offline';
    var pulseRing = isOnline ? '<div class="nms-pulse-ring"></div>' : '';

    var divIcon = L.divIcon({
        html: '<div style="position:relative;">' + pulseRing + '<div class="nms-device-icon ' + blinkClass + '"><img src="' + iconUrl + '"></div></div>',
        className: '',
        iconSize: [40, 40],
        iconAnchor: [20, 20],
        popupAnchor: [0, -20]
    });

    var marker = L.marker([lat, lng], {icon: divIcon}).addTo(map);
    marker.bindPopup(buildPopupHtml(d, isOnline), {
        closeButton: true,
        autoPan: false,
        autoClose: false,
        closeOnClick: false,
        maxWidth: 300
    });

    // Label nama device selalu tampil di peta (tidak hilang walau popup ditutup)
    var labelStatus = isOnline
        ? '<span class="nms-lbl-dot up"></span>'
        : (onlineDevices[d.id] === 'down' ? '<span class="nms-lbl-dot down"></span>' : '<span class="nms-lbl-dot wait"></span>');
    marker.bindTooltip(
        labelStatus + escapeHtml(d.nama),
        { permanent: true, direction: 'top', offset: [0, -20], className: 'nms-device-label', opacity: 1 }
    );

    marker.deviceData = d;
    marker.on('click', function (e) {
        if (fiberMode) {
            if (e.originalEvent) {
                L.DomEvent.stopPropagation(e);
            }
            marker.closePopup();
            handleFiberPick(d);
        }
    });
    return marker;
}

var markers = {};
var linkLines = [];

// ---- Fiber Line interaktif: klik device A lalu device B ----
var fiberMode = false;
var fiberDeviceA = null;

function setFiberButton(active) {
    var btn = document.getElementById('btn-fiber-line');
    if (!btn) return;
    if (active) {
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
        btn.innerHTML = '<i class="uil uil-times"></i> Batal Tarik Fiber';
    } else {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-success');
        btn.innerHTML = '<i class="uil uil-link-h"></i> Tarik Fiber Line';
    }
}

function toggleFiberMode() {
    fiberMode = !fiberMode;
    fiberDeviceA = null;
    setFiberButton(fiberMode);

    var mapEl = document.getElementById('nms-map');
    if (mapEl) {
        mapEl.style.cursor = fiberMode ? 'crosshair' : '';
    }

    if (fiberMode) {
        if (typeof toastr !== 'undefined') {
            toastr.info('Klik device pertama (sumber) di peta.');
        }
    }
}

function handleFiberPick(device) {
    if (!device.latitude || !device.longitude) {
        Swal.fire('Tidak bisa', 'Device "' + device.nama + '" belum punya koordinat. Edit device dan isi lokasinya dulu.', 'warning');
        return;
    }

    if (!fiberDeviceA) {
        fiberDeviceA = device;
        if (typeof toastr !== 'undefined') {
            toastr.success('Device A: ' + device.nama + '. Sekarang klik device tujuan.');
        }
        return;
    }

    if (fiberDeviceA.id === device.id) {
        Swal.fire('Tidak bisa', 'Device tujuan harus berbeda dari device sumber.', 'warning');
        return;
    }

    openLinkModalWith(fiberDeviceA, device);

    // reset mode setelah pasangan terpilih
    fiberMode = false;
    fiberDeviceA = null;
    setFiberButton(false);
    var mapEl = document.getElementById('nms-map');
    if (mapEl) {
        mapEl.style.cursor = '';
    }
}

function openLinkModalWith(deviceA, deviceB) {
    var form = document.querySelector('#linkModal form');
    if (form) {
        var selA = form.querySelector('[name="device_a_id"]');
        var selB = form.querySelector('[name="device_b_id"]');
        if (selA) selA.value = deviceA.id;
        if (selB) selB.value = deviceB.id;
    }
    var modalEl = document.getElementById('linkModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
    if (typeof loadDevicePorts === 'function') {
        loadDevicePorts('a');
        loadDevicePorts('b');
    }
}

document.getElementById('btn-fiber-line').addEventListener('click', toggleFiberMode);

// Warna & style per tipe link
function fiberStyle(linkType) {
    if (linkType === 'wireless') {
        return { color: '#5b73e8', dashArray: '6, 8', followRoad: false, flow: false };
    }
    if (linkType === 'copper') {
        return { color: '#f1b44c', dashArray: null, followRoad: true, flow: true };
    }
    return { color: '#34c759', dashArray: null, followRoad: true, flow: true }; // fiber
}

// Ambil rute jalan dari OSRM (gratis, tanpa API key). Mengembalikan
// array [lat,lng] mengikuti jalan, atau null bila gagal.
function fetchRoadRoute(pointA, pointB) {
    var url = 'https://router.project-osrm.org/route/v1/driving/' +
        pointA[1] + ',' + pointA[0] + ';' + pointB[1] + ',' + pointB[0] +
        '?overview=full&geometries=geojson';

    return fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res && res.routes && res.routes.length > 0) {
                // GeoJSON coords = [lng, lat] -> balik ke [lat, lng]
                return res.routes[0].geometry.coordinates.map(function (c) {
                    return [c[1], c[0]];
                });
            }
            return null;
        })
        .catch(function () { return null; });
}

// Gambar link di peta. Fiber/copper mengikuti jalan (routing), lalu
// disambung garis lurus tipis dari device ke titik jalan terdekat.
function drawFiberLink(link, pointA, pointB) {
    var style = fiberStyle(link.link_type);
    var label = (link.device_a && link.device_a.nama ? link.device_a.nama : 'A') +
        ' \u2192 ' + (link.device_b && link.device_b.nama ? link.device_b.nama : 'B') +
        ' (' + String(link.link_type).toUpperCase() + ')';

    function addPolyline(latlngs, isRoad) {
        var baseClass = link.link_type === 'wireless' ? 'nms-flow-wireless' : '';
        var polyline = L.polyline(latlngs, {
            color: style.color,
            weight: isRoad ? 4 : 3,
            opacity: 0.85,
            dashArray: style.dashArray,
            className: baseClass
        }).addTo(map);
        polyline.bindTooltip(label + (isRoad ? '' : ' - garis lurus'), { sticky: true });
        linkLines.push(polyline);

        // Overlay aliran (moving packets) untuk fiber/copper.
        if (style.flow) {
            var flow = L.polyline(latlngs, {
                color: '#ffffff',
                weight: isRoad ? 2 : 1.5,
                opacity: 0.9,
                interactive: false,
                className: 'nms-flow-dash'
            }).addTo(map);
            linkLines.push(flow);
        }
    }

    if (!style.followRoad) {
        addPolyline([pointA, pointB], false);
        return;
    }

    fetchRoadRoute(pointA, pointB).then(function (roadPath) {
        if (roadPath && roadPath.length > 1) {
            // Sambungkan device ke jalur jalan supaya tidak menggantung
            var full = [pointA].concat(roadPath).concat([pointB]);
            addPolyline(full, true);
        } else {
            // Fallback: OSRM gagal / tidak ada jalan -> garis lurus
            addPolyline([pointA, pointB], false);
        }
    });
}

fetch('{{ url("admin/nms/map-data") }}')
    .then(r => r.json())
    .then(res => {
        res.data.forEach(d => {
            markers[d.id] = renderMarker(d);
        });
        
        // Render links/lines
        if (res.links && res.links.length > 0) {
            res.links.forEach(link => {
                var latA = parseFloat(link.device_a.latitude);
                var lngA = parseFloat(link.device_a.longitude);
                var latB = parseFloat(link.device_b.latitude);
                var lngB = parseFloat(link.device_b.longitude);

                if (isNaN(latA) || isNaN(lngA) || isNaN(latB) || isNaN(lngB)) return;

                drawFiberLink(link, [latA, lngA], [latB, lngB]);
            });
        }

        return fetchStatusForMarkers(res.data);
    })
    .catch(err => console.error('Map data error:', err));

function fetchStatusForMarkers(devices) {
    devices.forEach(function(d) {
        fetch('{{ url("admin/nms/device/status") }}/' + d.id)
            .then(r => r.json())
            .then(res => {
                onlineDevices[d.id] = res.status;

                if (markers[d.id]) {
                    map.removeLayer(markers[d.id]);
                }
                markers[d.id] = renderMarker(d);

                var statusElement = document.getElementById('conn-status-' + d.id);
                if (statusElement) {
                    statusElement.className = res.status === 'up' ? 'badge bg-success' : 'badge bg-danger';
                    statusElement.textContent = res.status === 'up' ? 'UP' : 'DOWN';
                }

                // Auto-open popup untuk device online, lalu poll untuk ambil data SFP live
                if (res.status === 'up' && markers[d.id]) {
                    markers[d.id].openPopup();
                    // Poll device untuk dapat data SFP RX/TX terbaru
                    fetch('{{ url("admin/nms/device/poll") }}/' + d.id)
                        .then(r => r.json())
                        .then(pollRes => {
                            if (pollRes.error || !pollRes.ports) return;

                            // Extract SFP ports dari hasil poll
                            var sfpPorts = [];
                            pollRes.ports.forEach(function(p) {
                                var hasSfpData = (p.rx_power !== null && p.rx_power !== undefined && p.rx_power !== '') || 
                                                 (p.tx_power !== null && p.tx_power !== undefined && p.tx_power !== '');
                                if (hasSfpData) {
                                    sfpPorts.push({
                                        port_name: p.name,
                                        rx_power: p.rx_power,
                                        tx_power: p.tx_power,
                                        temperature: p.sfp_temperature,
                                        link_status: p.link_status,
                                        onu_count: p.onu_online
                                    });
                                }
                            });

                            // Update device data dengan SFP fresh
                            d.sfp_ports = sfpPorts;

                            // Re-render marker dan popup dengan data baru
                            if (markers[d.id]) {
                                var wasOpen = map.hasLayer(markers[d.id]) && markers[d.id].isPopupOpen();
                                map.removeLayer(markers[d.id]);
                                markers[d.id] = renderMarker(d);
                                if (wasOpen && markers[d.id]) {
                                    markers[d.id].openPopup();
                                }
                            }
                        })
                        .catch(() => {});
                }
            })
            .catch(() => {
                onlineDevices[d.id] = 'down';
                if (markers[d.id]) {
                    map.removeLayer(markers[d.id]);
                }
                markers[d.id] = renderMarker(d);

                var statusElement = document.getElementById('conn-status-' + d.id);
                if (statusElement) {
                    statusElement.className = 'badge bg-danger';
                    statusElement.textContent = 'DOWN';
                }
            });
    });
}


function fillPortDatalist(side, ports) {
    var list = document.getElementById('port_' + side + '_list');
    if (!list) return;
    list.innerHTML = '';

    (ports || []).forEach(function(port) {
        var name = port.name || port.port_name;
        if (!name) return;
        var option = document.createElement('option');
        option.value = name;
        var meta = [];
        if (port.link_status) meta.push(port.link_status);
        if (port.rx_power !== null && port.rx_power !== undefined && port.rx_power !== '') meta.push('RX ' + port.rx_power + ' dBm');
        if (port.tx_power !== null && port.tx_power !== undefined && port.tx_power !== '') meta.push('TX ' + port.tx_power + ' dBm');
        option.label = meta.length ? name + ' (' + meta.join(', ') + ')' : name;
        list.appendChild(option);
    });
}

function loadDevicePorts(side) {
    var select = document.getElementById('device_' + side + '_id');
    var input = document.getElementById('port_' + side);
    var loading = document.getElementById('port_' + side + '_loading');
    var id = select ? select.value : '';

    fillPortDatalist(side, []);
    if (input) input.value = '';
    if (!id) return;

    if (loading) loading.classList.remove('d-none');
    fetch('{{ url("admin/nms/device/poll") }}/' + id)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            fillPortDatalist(side, res.ports || []);
        })
        .catch(function() {
            fillPortDatalist(side, []);
        })
        .finally(function() {
            if (loading) loading.classList.add('d-none');
        });
}

['a', 'b'].forEach(function(side) {
    var select = document.getElementById('device_' + side + '_id');
    if (select) {
        select.addEventListener('change', function() {
            loadDevicePorts(side);
        });
    }
});

document.querySelectorAll('.swal-delete').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: this.dataset.text || 'Data akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f46a6a',
            cancelButtonColor: '#74788d'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = this.href;
            }
        });
    });
});
</script>
@endsection
