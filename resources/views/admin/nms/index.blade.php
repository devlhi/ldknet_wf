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
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0"><i class="uil uil-server me-1"></i> Daftar Device</h4>
                    </div>
                    <div class="card-body">
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
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-link me-1"></i> Link / Koneksi Antar Device</h4>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#linkModal">
                            <i class="uil uil-plus"></i> Tambah Link
                        </button>
                    </div>
                    <div class="card-body">
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
                        <select name="device_a_id" class="form-select" required>
                            <option value="">-- Pilih Device A --</option>
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->nama }} ({{ $device->ip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Port A (opsional)</label>
                        <input type="text" name="port_a" class="form-control" placeholder="contoh: sfp1, ether1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device B</label>
                        <select name="device_b_id" class="form-select" required>
                            <option value="">-- Pilih Device B --</option>
                            @foreach ($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->nama }} ({{ $device->ip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Port B (opsional)</label>
                        <input type="text" name="port_b" class="form-control" placeholder="contoh: sfp1, ether1">
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
@keyframes nms-blink {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.92); }
}
@keyframes nms-pulse-ring {
    0% { transform: scale(0.8); opacity: 0.8; }
    100% { transform: scale(2.2); opacity: 0; }
}
.nms-marker-online {
    animation: nms-blink 1.2s ease-in-out infinite;
}
.nms-marker-offline {
    opacity: 0.4;
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
.nms-pulse-ring {
    position: absolute;
    top: -3px;
    left: -3px;
    width: 46px;
    height: 46px;
    border-radius: 8px;
    border: 2px solid #34c759;
    animation: nms-pulse-ring 1.5s ease-out infinite;
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
.nms-popup-online {
    border-left: 4px solid #34c759;
    animation: nms-blink 1.2s ease-in-out infinite;
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

function buildPopupHtml(d, isOnline) {
    var iconUrl = deviceIconUrls[d.tipe] || deviceIconUrls.mikrotik;
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

    var sfpPorts = d.sfp_ports || [];
    // Filter out ports that don't have RX/TX data (like virtual tunnels, L2TP, etc)
    var validSfpPorts = sfpPorts.filter(function(p) {
        return (p.rx_power !== null && p.rx_power !== undefined && p.rx_power !== '') || 
               (p.tx_power !== null && p.tx_power !== undefined && p.tx_power !== '') ||
               p.port_name.toLowerCase().includes('sfp');
    });

    var isOlt = d.tipe === 'olt';
    if (validSfpPorts.length > 0) {
        html += '<table class="nms-sfp-table" style="font-size:11px; margin-top:5px; width:100%;">';
        if (isOlt) {
            html += '<thead><tr><th>Port</th><th>RX</th><th>TX</th></tr></thead><tbody>';
            validSfpPorts.forEach(function(p) {
                html += '<tr>';
                html += '<td>' + escapeHtml(p.port_name) + '</td>';
                html += '<td>' + sfpBadge(p.rx_power) + '</td>';
                html += '<td>' + sfpBadge(p.tx_power) + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<thead><tr><th>Port</th><th>RX</th><th>TX</th></tr></thead><tbody>';
            validSfpPorts.forEach(function(p) {
                html += '<tr>';
                html += '<td>' + escapeHtml(p.port_name) + '</td>';
                html += '<td>' + sfpBadge(p.rx_power) + '</td>';
                html += '<td>' + sfpBadge(p.tx_power) + '</td>';
                html += '</tr>';
            });
        }
        html += '</tbody></table>';
    } else {
        html += '<small style="color:#aaa;">Tidak ada modul SFP / RX TX kosong</small><br>';
    }

    html += '<a href="{{ url("admin/nms/device/detail") }}/' + d.id + '" class="btn btn-sm btn-info text-white mt-2" style="font-size:11px;"><i class="uil uil-eye"></i> Detail</a>';
    html += '</div>';

    return html;
}

function renderMarker(d) {
    var lat = parseFloat(d.latitude);
    var lng = parseFloat(d.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    var isOnline = onlineDevices[d.id] === 'up';
    var iconUrl = deviceIconUrls[d.tipe] || deviceIconUrls.mikrotik;
    var blinkClass = isOnline ? 'nms-marker-online' : 'nms-marker-offline';
    var pulseRing = isOnline ? '<div class="nms-pulse-ring"></div>' : '';

    var divIcon = L.divIcon({
        html: '<div style="position:relative;' + blinkClass + '">' + pulseRing + '<div class="nms-device-icon"><img src="' + iconUrl + '"></div></div>',
        className: '',
        iconSize: [40, 40],
        iconAnchor: [20, 20],
        popupAnchor: [0, -20]
    });

    var marker = L.marker([lat, lng], {icon: divIcon}).addTo(map);
    marker.bindPopup(buildPopupHtml(d, isOnline), {closeButton: true, autoPan: true, maxWidth: 300});
    marker.deviceData = d;
    return marker;
}

var markers = {};
var linkLines = [];

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
                
                var color = '#34c759'; // default fiber (green)
                var dashArray = null;
                
                if (link.link_type === 'wireless') {
                    color = '#5b73e8'; // blue
                    dashArray = '5, 5';
                } else if (link.link_type === 'copper') {
                    color = '#f1b44c'; // orange
                }
                
                var polyline = L.polyline([[latA, lngA], [latB, lngB]], {
                    color: color,
                    weight: 3,
                    opacity: 0.8,
                    dashArray: dashArray
                }).addTo(map);
                
                linkLines.push(polyline);
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
                                                 (p.tx_power !== null && p.tx_power !== undefined && p.tx_power !== '') ||
                                                 (p.name && p.name.toLowerCase().includes('sfp'));
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
            });
    });
}

var deviceIds = @json($devices->pluck('id'));
deviceIds.forEach(function(id) {
    var el = document.getElementById('conn-status-' + id);
    if (!el) return;
    fetch('{{ url("admin/nms/device/status") }}/' + id)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'up') {
                el.className = 'badge bg-success';
                el.textContent = 'UP';
            } else {
                el.className = 'badge bg-danger';
                el.textContent = 'DOWN';
            }
        })
        .catch(() => {
            el.className = 'badge bg-danger';
            el.textContent = 'DOWN';
        });
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
