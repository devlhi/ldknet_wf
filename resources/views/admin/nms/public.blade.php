<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titletext ?? 'LandakNet' }} - Network Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <style>
        body { margin: 0; padding: 0; background: #f3f3f3; font-family: system-ui, -apple-system, sans-serif; overflow: hidden; }
        .monitor-header {
            position: absolute; top: 10px; left: 50px; right: 10px; z-index: 1000;
            background: linear-gradient(135deg, rgba(26,26,46,0.95), rgba(22,33,62,0.95));
            color: white; padding: 12px 24px; display: flex; align-items: center; gap: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,.3); border-radius: 8px; backdrop-filter: blur(5px);
        }
        .monitor-header h4 { margin: 0; font-size: 18px; }
        .monitor-header .logo img { max-height: 36px; max-width: 120px; }
        .monitor-stats { display: flex; gap: 16px; margin-left: auto; }
        .stat-card { background: rgba(255,255,255,.1); padding: 6px 14px; border-radius: 8px; text-align: center; }
        .stat-card .num { font-size: 22px; font-weight: 700; line-height: 1.1; }
        .stat-card .lbl { font-size: 11px; opacity: 0.8; }
        #nms-map { width: 100%; height: 100vh; position: absolute; top: 0; left: 0; z-index: 1; }
        @keyframes nms-blink { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:0.4; transform:scale(0.92); } }
        @keyframes nms-pulse-ring { 0% { transform:scale(0.8); opacity:0.8; } 100% { transform:scale(2.2); opacity:0; } }
        .nms-marker-online { animation: nms-blink 1.2s ease-in-out infinite; }
        .nms-marker-offline { opacity: 0.4; filter: grayscale(1); }
        .nms-device-icon {
            width: 40px; height: 40px; border-radius: 8px; border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,.5); overflow: hidden;
            display: flex; align-items: center; justify-content: center; background: #fff;
        }
        .nms-device-icon img { width: 100%; height: 100%; object-fit: contain; }
        .nms-pulse-ring {
            position: absolute; top: -3px; left: -3px; width: 46px; height: 46px;
            border-radius: 8px; border: 2px solid #34c759; animation: nms-pulse-ring 1.5s ease-out infinite;
        }
        .nms-sfp-table { font-size: 11px; border-collapse: collapse; width: 100%; margin-top: 4px; }
        .nms-sfp-table th { padding: 3px 6px; border: 1px solid #dee2e6; background: #f8f9fa; text-align: center; }
        .nms-sfp-table td { padding: 3px 6px; border: 1px solid #dee2e6; text-align: center; }
        .nms-popup-online { border-left: 4px solid #34c759; animation: nms-blink 1.2s ease-in-out infinite; }
        .nms-popup-offline { border-left: 4px solid #f46a6a; opacity: 0.85; }
        .leaflet-popup-content { margin: 8px 12px; }
        .leaflet-popup-content-wrapper { border-radius: 8px; }
        .badge { font-size: 10px; }
        .bg-success { background-color: #34c759 !important; }
        .bg-danger { background-color: #f46a6a !important; }
        .bg-warning { background-color: #f1b44c !important; }
        .bg-secondary { background-color: #74788d !important; }
        .bg-info { background-color: #50a5f1 !important; }
    </style>
</head>
<body>
    <div id="nms-map"></div>

    <div class="monitor-header">
        @if (!empty($logo))
            <div class="logo"><img src="{{ asset($logo) }}" alt="Logo" style="max-height:36px;filter:brightness(0) invert(1);"></div>
        @endif
        <h4><i class="fas fa-network-wired"></i> {{ $titletext ?? 'LandakNet' }} - Network Monitor</h4>
        <div class="monitor-stats">
            <div class="stat-card"><div class="num" id="stat-total" style="color:#50a5f1;">-</div><div class="lbl">Total Device</div></div>
            <div class="stat-card"><div class="num" id="stat-online" style="color:#34c759;">-</div><div class="lbl">Online</div></div>
            <div class="stat-card"><div class="num" id="stat-offline" style="color:#f46a6a;">-</div><div class="lbl">Offline</div></div>
        </div>
    </div>

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

    var onlineDevices = {};
    var markers = {};
    var linkLines = [];

    function buildPopupHtml(d, isOnline) {
        var iconUrl = deviceIconUrls[d.tipe] || deviceIconUrls.mikrotik;
        var popupClass = isOnline ? 'nms-popup-online' : 'nms-popup-offline';
        var statusBadge = isOnline
            ? '<span class="badge bg-success">ONLINE</span>'
            : '<span class="badge bg-danger">OFFLINE</span>';

        var html = '<div class="' + popupClass + '" style="min-width:220px;padding-left:8px;">';
        html += '<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">';
        html += '<img src="' + iconUrl + '" width="32" height="32" style="border-radius:6px;object-fit:contain;">';
        html += '<div><strong>' + escapeHtml(d.nama) + '</strong> ' + statusBadge + '<br>';
        html += '<small style="color:#888;">' + escapeHtml(d.tipe.toUpperCase()) + ' | ' + escapeHtml(d.ip) + '</small></div>';
        html += '</div>';

        var sfpPorts = d.sfp_ports || [];
        var validSfpPorts = sfpPorts.filter(function(p) {
            return (p.rx_power !== null && p.rx_power !== undefined && p.rx_power !== '') ||
                   (p.tx_power !== null && p.tx_power !== undefined && p.tx_power !== '');
        });

        if (validSfpPorts.length > 0) {
            html += '<table class="nms-sfp-table"><thead><tr><th>Port</th><th>RX</th><th>TX</th></tr></thead><tbody>';
            validSfpPorts.forEach(function(p) {
                html += '<tr><td>' + escapeHtml(p.port_name) + '</td><td>' + sfpBadge(p.rx_power) + '</td><td>' + sfpBadge(p.tx_power) + '</td></tr>';
            });
            html += '</tbody></table>';
        } else {
            html += '<small style="color:#aaa;">Tidak ada modul SFP</small><br>';
        }

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
            html: '<div style="position:relative;" class="' + blinkClass + '">' + pulseRing + '<div class="nms-device-icon"><img src="' + iconUrl + '"></div></div>',
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

    osmLayer.addTo(map);

    L.control.layers({
        'OpenStreetMap': osmLayer,
        'Satelit (Esri)': satelliteLayer,
        'Terrain (Topo)': terrainLayer,
        'Light (Carto)': streetsLayer
    }, null, {
        position: 'bottomright',
        collapsed: true
    }).addTo(map);

    fetch('{{ url("nms/monitor/data/map") }}')
        .then(r => r.json())
        .then(res => {
            var total = res.data.length;
            var onlineCount = 0;
            var offlineCount = 0;

            res.data.forEach(d => {
                markers[d.id] = renderMarker(d);
            });

            if (res.links && res.links.length > 0) {
                res.links.forEach(link => {
                    var latA = parseFloat(link.device_a.latitude);
                    var lngA = parseFloat(link.device_a.longitude);
                    var latB = parseFloat(link.device_b.latitude);
                    var lngB = parseFloat(link.device_b.longitude);

                    if (isNaN(latA) || isNaN(lngA) || isNaN(latB) || isNaN(lngB)) return;

                    var color = '#34c759';
                    var dashArray = null;

                    if (link.link_type === 'wireless') {
                        color = '#5b73e8';
                        dashArray = '5, 5';
                    } else if (link.link_type === 'copper') {
                        color = '#f1b44c';
                    }

                    var polyline = L.polyline([[latA, lngA], [latB, lngB]], {
                        color: color, weight: 3, opacity: 0.8, dashArray: dashArray
                    }).addTo(map);

                    linkLines.push(polyline);
                });
            }

            res.data.forEach(function(d) {
                fetch('{{ url("nms/monitor/data/status") }}/' + d.id)
                    .then(r => r.json())
                    .then(res2 => {
                        onlineDevices[d.id] = res2.status;
                        if (res2.status === 'up') onlineCount++; else offlineCount++;
                        document.getElementById('stat-total').textContent = total;
                        document.getElementById('stat-online').textContent = onlineCount;
                        document.getElementById('stat-offline').textContent = offlineCount;

                        if (markers[d.id]) {
                            map.removeLayer(markers[d.id]);
                            markers[d.id] = renderMarker(d);

                            if (res2.status === 'up' && markers[d.id]) {
                                markers[d.id].openPopup();
                                fetch('{{ url("admin/nms/device/poll") }}/' + d.id)
                                    .then(r => r.json())
                                    .then(pollRes => {
                                        if (pollRes.error || !pollRes.ports) return;
                                        var sfpPorts = [];
                                        pollRes.ports.forEach(function(p) {
                                            var hasSfpData = (p.rx_power !== null && p.rx_power !== undefined && p.rx_power !== '') ||
                                                             (p.tx_power !== null && p.tx_power !== undefined && p.tx_power !== '');
                                            if (hasSfpData) {
                                                sfpPorts.push({
                                                    port_name: p.name,
                                                    rx_power: p.rx_power,
                                                    tx_power: p.tx_power
                                                });
                                            }
                                        });
                                        d.sfp_ports = sfpPorts;
                                        if (markers[d.id]) {
                                            var wasOpen = markers[d.id].isPopupOpen();
                                            map.removeLayer(markers[d.id]);
                                            markers[d.id] = renderMarker(d);
                                            if (wasOpen && markers[d.id]) {
                                                markers[d.id].openPopup();
                                            }
                                        }
                                    })
                                    .catch(() => {});
                            }
                        }
                    })
                    .catch(() => {
                        onlineDevices[d.id] = 'down';
                        offlineCount++;
                        document.getElementById('stat-total').textContent = total;
                        document.getElementById('stat-online').textContent = onlineCount;
                        document.getElementById('stat-offline').textContent = offlineCount;
                        if (markers[d.id]) {
                            map.removeLayer(markers[d.id]);
                            markers[d.id] = renderMarker(d);
                        }
                    });
            });
        })
        .catch(err => console.error('Map data error:', err));

    setInterval(function() { location.reload(); }, 60000);
    </script>
</body>
</html>
