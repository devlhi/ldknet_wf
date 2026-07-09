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
        @keyframes nms-pulse-ring { 0% { transform:scale(0.8); opacity:0.8; } 100% { transform:scale(2.2); opacity:0; } }
        .nms-device-icon.nms-marker-online { border-color: #34c759; }
        .nms-device-icon.nms-marker-offline { opacity: 0.55; filter: grayscale(1); }
        .nms-device-icon {
            width: 40px; height: 40px; border-radius: 8px; border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,.5); overflow: hidden;
            display: flex; align-items: center; justify-content: center; background: #fff;
        }
        .nms-device-icon img { width: 100%; height: 100%; object-fit: contain; }
        .nms-device-label {
            background: rgba(255,255,255,.95); border: 1px solid rgba(0,0,0,.12);
            border-radius: 14px; box-shadow: 0 2px 6px rgba(0,0,0,.18);
            color: #2f3640; font-size: 11px; font-weight: 600; padding: 2px 8px;
        }
        .nms-device-label::before { display: none; }
        .nms-lbl-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 5px; }
        .nms-lbl-dot.up { background: #34c759; }
        .nms-lbl-dot.down { background: #f46a6a; }
        .nms-lbl-dot.wait { background: #74788d; }
        .nms-pulse-ring {
            position: absolute; top: -3px; left: -3px; width: 46px; height: 46px;
            border-radius: 8px; border: 2px solid #34c759; animation: nms-pulse-ring 1.5s ease-out infinite;
        }
        .nms-sfp-table { font-size: 11px; border-collapse: collapse; width: 100%; margin-top: 4px; }
        .nms-sfp-table th { padding: 3px 6px; border: 1px solid #dee2e6; background: #f8f9fa; text-align: center; }
        .nms-sfp-table td { padding: 3px 6px; border: 1px solid #dee2e6; text-align: center; }
        .nms-sfp-pager { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 4px; font-size: 10px; color: #74788d; }
        .nms-sfp-page-ind { background: #f1f3f5; border-radius: 10px; padding: 1px 8px; font-weight: 600; }
        .nms-popup-online { border-left: 4px solid #34c759; }
        .nms-popup-offline { border-left: 4px solid #f46a6a; opacity: 0.85; }
        .leaflet-popup-content { margin: 8px 12px; }
        .leaflet-popup-content-wrapper { border-radius: 8px; }
        .badge { font-size: 10px; }
        .bg-success { background-color: #34c759 !important; }
        .bg-danger { background-color: #f46a6a !important; }
        .bg-warning { background-color: #f1b44c !important; }
        .bg-secondary { background-color: #74788d !important; }
        .bg-info { background-color: #50a5f1 !important; }
        /* Animasi aliran data pada kabel fiber/copper */
        @keyframes nms-fiber-flow { to { stroke-dashoffset: -1000; } }
        .nms-fiber-flow { stroke-dasharray: 12 10; animation: nms-fiber-flow 30s linear infinite; }
    </style>
</head>
<body>
    <div id="nms-map"></div>

    <div class="monitor-header">
        @if (!empty($logo))
            <div class="logo"><img src="{{ asset('assets/logo/'.$logo) }}" alt="Logo" style="max-height:36px;filter:brightness(0) invert(1);"></div>
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

    // Perangkat vendor Mikrotik (RouterOS/CRS/CCR/RouterBoard) pakai ikon Mikrotik.
    function iconForDevice(d) {
        var tipe = String(d.tipe || '').toLowerCase();
        var hay = (String(d.nama || '') + ' ' + String(d.vendor || '') + ' ' + String(d.routerboard_model || '')).toLowerCase();
        if (tipe === 'mikrotik' || tipe === 'crs' || /mikrotik|routeros|routerboard|\bcrs\b|\bccr\b|\brb\d/.test(hay)) {
            return deviceIconUrls.mikrotik;
        }
        return deviceIconUrls[tipe] || deviceIconUrls.snmp;
    }

    var onlineDevices = {};
    var markers = {};
    var linkLines = [];

    function sfpHasPower(p) {
        return (p.rx_power !== null && p.rx_power !== undefined && p.rx_power !== '') ||
               (p.tx_power !== null && p.tx_power !== undefined && p.tx_power !== '');
    }

    function sfpTableBlock(ports) {
        var t = '<table class="nms-sfp-table"><thead><tr><th>Port</th><th>RX</th><th>TX</th></tr></thead><tbody>';
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

    // Style per tipe link. Fiber/copper mengikuti jalan; wireless lurus.
    function fiberStyle(linkType) {
        if (linkType === 'wireless') {
            return { color: '#5b73e8', dashArray: '6, 8', followRoad: false, flow: false };
        }
        if (linkType === 'copper') {
            return { color: '#f1b44c', dashArray: null, followRoad: true, flow: true };
        }
        return { color: '#34c759', dashArray: null, followRoad: true, flow: true };
    }

    // Ambil rute jalan dari OSRM publik (tanpa API key). null bila gagal.
    function fetchRoadRoute(pointA, pointB) {
        var url = 'https://router.project-osrm.org/route/v1/driving/' +
            pointA[1] + ',' + pointA[0] + ';' + pointB[1] + ',' + pointB[0] +
            '?overview=full&geometries=geojson';
        return fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.routes && res.routes.length > 0) {
                    return res.routes[0].geometry.coordinates.map(function (c) { return [c[1], c[0]]; });
                }
                return null;
            })
            .catch(function () { return null; });
    }

    // Gambar link: fiber/copper mengikuti jalan + animasi aliran; fallback lurus.
    function drawFiberLink(link, pointA, pointB) {
        var style = fiberStyle(link.link_type);
        var label = (link.device_a && link.device_a.nama ? link.device_a.nama : 'A') +
            ' \u2192 ' + (link.device_b && link.device_b.nama ? link.device_b.nama : 'B') +
            ' (' + String(link.link_type).toUpperCase() + ')';

        function addPolyline(latlngs, isRoad) {
            var polyline = L.polyline(latlngs, {
                color: style.color,
                weight: isRoad ? 4 : 3,
                opacity: 0.85,
                dashArray: style.flow ? null : style.dashArray,
                className: style.flow ? 'nms-fiber-flow' : ''
            }).addTo(map);
            polyline.bindTooltip(label, { sticky: true });
            linkLines.push(polyline);
        }

        if (!style.followRoad) {
            addPolyline([pointA, pointB], false);
            return;
        }

        fetchRoadRoute(pointA, pointB).then(function (roadPath) {
            if (roadPath && roadPath.length > 1) {
                addPolyline([pointA].concat(roadPath).concat([pointB]), true);
            } else {
                addPolyline([pointA, pointB], false);
            }
        });
    }

    function buildPopupHtml(d, isOnline) {
        var iconUrl = iconForDevice(d);
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

        html += buildSfpTableHtml(d.sfp_ports);

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

        // Label nama device selalu tampil (data langsung terlihat tanpa klik)
        var dot = isOnline ? 'up' : (onlineDevices[d.id] === 'down' ? 'down' : 'wait');
        marker.bindTooltip(
            '<span class="nms-lbl-dot ' + dot + '"></span>' + escapeHtml(d.nama),
            { permanent: true, direction: 'top', offset: [0, -20], className: 'nms-device-label', opacity: 1 }
        );

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

    var mapDataUrl = '{{ url("nms/monitor/data/map") }}';
    var statusBaseUrl = '{{ url("nms/monitor/data/status") }}/';
    var pollBaseUrl = '{{ url("admin/nms/device/poll") }}/';
    var refreshInProgress = false;

    function setStats(total, online, offline) {
        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-online').textContent = online;
        document.getElementById('stat-offline').textContent = offline;
    }

    function getOpenDeviceId() {
        for (var id in markers) {
            if (markers[id] && markers[id].isPopupOpen && markers[id].isPopupOpen()) {
                return String(id);
            }
        }
        return null;
    }

    function clearLinks() {
        linkLines.forEach(function(line) {
            map.removeLayer(line);
        });
        linkLines = [];
    }

    function renderLinks(links) {
        clearLinks();
        links.forEach(function(link) {
            var latA = parseFloat(link.device_a.latitude);
            var lngA = parseFloat(link.device_a.longitude);
            var latB = parseFloat(link.device_b.latitude);
            var lngB = parseFloat(link.device_b.longitude);

            if (isNaN(latA) || isNaN(lngA) || isNaN(latB) || isNaN(lngB)) return;

            drawFiberLink(link, [latA, lngA], [latB, lngB]);
        });
    }

    function pollSfpPorts(d, shouldOpenPopup) {
        if (onlineDevices[d.id] !== 'up') return;

        fetch(pollBaseUrl + d.id)
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
                    map.removeLayer(markers[d.id]);
                }
                markers[d.id] = renderMarker(d);
                if (shouldOpenPopup && markers[d.id]) {
                    markers[d.id].openPopup();
                }
            })
            .catch(() => {});
    }

    function renderDeviceMarkers(devices, openDeviceId) {
        var activeIds = {};
        devices.forEach(function(d) {
            activeIds[d.id] = true;
        });

        Object.keys(markers).forEach(function(id) {
            if (!activeIds[id]) {
                map.removeLayer(markers[id]);
                delete markers[id];
            }
        });

        devices.forEach(function(d) {
            var shouldOpenPopup = onlineDevices[d.id] === 'up' || String(d.id) === String(openDeviceId);
            if (markers[d.id]) {
                map.removeLayer(markers[d.id]);
            }
            markers[d.id] = renderMarker(d);
            if (shouldOpenPopup && markers[d.id]) {
                markers[d.id].openPopup();
            }
            pollSfpPorts(d, shouldOpenPopup);
        });
    }

    function refreshDeviceStatuses(devices, openDeviceId) {
        var total = devices.length;
        if (total === 0) {
            setStats(0, 0, 0);
            renderDeviceMarkers([], null);
            return Promise.resolve();
        }

        return Promise.all(devices.map(function(d) {
            return fetch(statusBaseUrl + d.id)
                .then(r => r.json())
                .then(function(res2) {
                    return { device: d, status: res2.status === 'up' ? 'up' : 'down' };
                })
                .catch(function() {
                    return { device: d, status: 'down' };
                });
        })).then(function(results) {
            var onlineCount = 0;
            var offlineCount = 0;
            var freshDevices = [];

            results.forEach(function(item) {
                onlineDevices[item.device.id] = item.status;
                if (item.status === 'up') onlineCount++; else offlineCount++;
                freshDevices.push(item.device);
            });

            setStats(total, onlineCount, offlineCount);
            renderDeviceMarkers(freshDevices, openDeviceId);
        });
    }

    function loadMonitorData() {
        if (refreshInProgress) return;
        refreshInProgress = true;
        var openDeviceId = getOpenDeviceId();

        fetch(mapDataUrl)
            .then(r => r.json())
            .then(function(res) {
                var devices = res.data || [];
                renderLinks(res.links || []);
                return refreshDeviceStatuses(devices, openDeviceId);
            })
            .catch(function(err) {
                console.error('Map data error:', err);
            })
            .finally(function() {
                refreshInProgress = false;
            });
    }

    loadMonitorData();
    setInterval(loadMonitorData, 60000);
    </script>
</body>
</html>
