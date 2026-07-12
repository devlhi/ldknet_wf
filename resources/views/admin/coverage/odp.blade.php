@extends('admin.layout')

@section('css')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Coverage ODP</h4>
                        <button type="button" class="btn btn-success" id="btnAddOdp"><i class="mdi mdi-plus me-1"></i> Tambah ODP</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    @if (session('auth_errors'))
                        <div class="alert alert-danger" role="alert">
                            @foreach (session('auth_errors') as $err)<p class="mb-0">{{ $err }}</p>@endforeach
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2"></i>
                            @foreach (session('success') as $suc){{ $suc }}@endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-3 mb-2 small text-muted">
                                <span><i class="mdi mdi-circle text-primary"></i> ODP normal</span>
                                <span><i class="mdi mdi-circle text-danger"></i> ODP ada laporan gangguan terbuka</span>
                                <span><i class="mdi mdi-circle text-secondary"></i> ODP belum ada koordinat (tidak tampil di peta)</span>
                            </div>
                            <div id="map" style="height: 500px; border-radius: 6px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatable" class="table table-bordered dt-responsive nowrap align-middle" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama ODP</th>
                                            <th>Kode</th>
                                            <th>Total Port</th>
                                            <th>Terpakai</th>
                                            <th>Sisa Port</th>
                                            <th>Pelanggan</th>
                                            <th>Koordinat</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($i = 1)
                                        @foreach ($data as $row)
                                            <tr>
                                                <td>{{ $i++ }}</td>
                                                <td>{{ $row['nama'] }}</td>
                                                <td>{{ $row['kode'] ?: '-' }}</td>
                                                <td>{{ $row['total_port'] }}</td>
                                                <td>{{ $row['used_ports'] }}</td>
                                                <td>{{ count($row['available_ports']) ? implode(', ', $row['available_ports']) : '—' }}</td>
                                                <td>{{ $row['pelanggan'] }}</td>
                                                <td>
                                                    @if ($row['latitude'] && $row['longitude'])
                                                        <span class="text-success"><i class="mdi mdi-map-marker-check"></i> Ada</span>
                                                    @else
                                                        <span class="text-muted"><i class="mdi mdi-map-marker-off"></i> Belum</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($row['gangguan_open'] > 0)
                                                        <span class="badge bg-danger">{{ $row['gangguan_open'] }} gangguan</span>
                                                    @else
                                                        <span class="badge bg-soft-success text-success">Normal</span>
                                                    @endif
                                                </td>
                                                <td class="text-nowrap">
                                                    <button type="button" class="btn btn-sm btn-primary btn-edit-odp"
                                                        data-id="{{ $row['id'] }}"
                                                        data-nama="{{ $row['nama'] }}"
                                                        data-kode="{{ $row['kode'] }}"
                                                        data-port="{{ $row['total_port'] }}"
                                                        data-lat="{{ $row['latitude'] }}"
                                                        data-lng="{{ $row['longitude'] }}"><i class="mdi mdi-pencil"></i></button>
                                                    <a href="{{ url('admin/coverage/odp/delete/'.$row['id']) }}" class="btn btn-sm btn-danger swal-delete"
                                                        data-text="Hapus ODP {{ $row['nama'] }}? Tindakan ini tidak bisa dibatalkan."><i class="mdi mdi-delete"></i></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal form ODP (dipakai untuk Tambah & Edit) --}}
    <div id="odpModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="odpForm" action="{{ url('admin/coverage/odp/add') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title" id="odpModalTitle"><i class="mdi mdi-plus me-1"></i> Tambah ODP</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="position-relative mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input type="text" class="form-control" id="odpSearch" placeholder="Cari nama lokasi/alamat ODP... (mis. Dusun Ngabang)" autocomplete="off">
                            </div>
                            <div id="odpSearchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 240px; overflow-y: auto; display: none;"></div>
                        </div>

                        <div id="odpPicker" style="height: 300px; border-radius: 6px;" class="mb-3"></div>
                        <small class="text-muted d-block mb-3">Cari lokasi di atas, klik titik di peta, atau ketik koordinat manual — semuanya saling menyinkronkan peta.</small>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nama ODP</label>
                                <input type="text" name="nama" id="odpNama" class="form-control" placeholder="Masukan Nama ODP" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kode (opsional)</label>
                                <input type="text" name="kode" id="odpKode" class="form-control" placeholder="Kode ODP">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Jumlah Port</label>
                                <input type="number" name="jumlah" id="odpPort" class="form-control" min="1" placeholder="mis. 8" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Latitude</label>
                                <input type="text" name="latitude" id="odpLat" class="form-control" placeholder="mis. -0.4512300">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Longitude</label>
                                <input type="text" name="longitude" id="odpLng" class="form-control" placeholder="mis. 109.7891200">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
(function () {
    var odpData = @json($data);
    var addUrl = "{{ url('admin/coverage/odp/add') }}";
    var updateBase = "{{ url('admin/coverage/odp/update') }}";

    // ---------------- Peta utama: semua ODP ----------------
    var map = L.map('map').setView([-0.12, 109.15], 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    }).addTo(map);

    var bounds = [];
    odpData.forEach(function (o) {
        var lat = parseFloat(o.latitude);
        var lng = parseFloat(o.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        var warna = o.gangguan_open > 0 ? '#f46a6a' : '#556ee6';
        var marker = L.circleMarker([lat, lng], {
            radius: 9, color: warna, fillColor: warna, fillOpacity: 0.7, weight: 2
        }).addTo(map);

        // Popup dibangun via DOM (textContent) agar aman dari HTML injection nama ODP.
        var box = document.createElement('div');
        var judul = document.createElement('b');
        judul.textContent = o.nama || '(tanpa nama)';
        box.appendChild(judul);
        if (o.kode) { box.appendChild(document.createElement('br')); box.appendChild(document.createTextNode('Kode: ' + o.kode)); }
        box.appendChild(document.createElement('br'));
        box.appendChild(document.createTextNode('Port: ' + o.used_ports + '/' + o.total_port + ' terpakai'));
        box.appendChild(document.createElement('br'));
        box.appendChild(document.createTextNode('Pelanggan: ' + o.pelanggan));
        if (o.gangguan_open > 0) {
            box.appendChild(document.createElement('br'));
            var g = document.createElement('span');
            g.style.color = '#f46a6a';
            g.style.fontWeight = 'bold';
            g.textContent = '⚠ ' + o.gangguan_open + ' laporan gangguan terbuka';
            box.appendChild(g);
        }
        marker.bindPopup(box);
        bounds.push([lat, lng]);
    });

    if (bounds.length === 1) {
        map.setView(bounds[0], 15);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40] });
    }

    // ---------------- Modal form (Tambah / Edit) ----------------
    var modalEl = document.getElementById('odpModal');
    var modal = new bootstrap.Modal(modalEl);
    var form = document.getElementById('odpForm');
    var fNama = document.getElementById('odpNama');
    var fKode = document.getElementById('odpKode');
    var fPort = document.getElementById('odpPort');
    var fLat = document.getElementById('odpLat');
    var fLng = document.getElementById('odpLng');
    var titleEl = document.getElementById('odpModalTitle');

    var picker = null, pMarker = null, pInit = false, syncing = false;

    function initPicker() {
        picker = L.map('odpPicker').setView([-0.12, 109.15], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
        }).addTo(picker);
        picker.on('click', function (e) { setPoint(e.latlng.lat, e.latlng.lng, false); });
        pInit = true;
    }

    function setPoint(lat, lng, recenter) {
        if (pMarker) picker.removeLayer(pMarker);
        pMarker = L.marker([lat, lng]).addTo(picker);
        syncing = true;
        fLat.value = Number(lat).toFixed(7);
        fLng.value = Number(lng).toFixed(7);
        syncing = false;
        if (recenter) picker.setView([lat, lng], Math.max(picker.getZoom(), 15));
    }

    function openModal(mode, d) {
        if (mode === 'edit') {
            titleEl.innerHTML = '<i class="mdi mdi-pencil me-1"></i> Edit ODP';
            form.action = updateBase + '/' + d.id;
            fNama.value = d.nama || '';
            fKode.value = d.kode || '';
            fPort.value = d.port || '';
            fLat.value = d.lat || '';
            fLng.value = d.lng || '';
        } else {
            titleEl.innerHTML = '<i class="mdi mdi-plus me-1"></i> Tambah ODP';
            form.action = addUrl;
            form.reset();
        }
        document.getElementById('odpSearch').value = '';
        hideResults();
        modal.show();
    }

    modalEl.addEventListener('shown.bs.modal', function () {
        if (!pInit) initPicker();
        picker.invalidateSize();
        if (pMarker) picker.removeLayer(pMarker), pMarker = null;
        var lat = parseFloat(fLat.value), lng = parseFloat(fLng.value);
        if (!isNaN(lat) && !isNaN(lng)) setPoint(lat, lng, true);
    });

    document.getElementById('btnAddOdp').addEventListener('click', function () { openModal('add'); });
    // Delegasi: tetap jalan walau DataTables memaginasi/menyusun ulang baris.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-edit-odp');
        if (!btn) return;
        openModal('edit', {
            id: btn.dataset.id, nama: btn.dataset.nama, kode: btn.dataset.kode,
            port: btn.dataset.port, lat: btn.dataset.lat, lng: btn.dataset.lng
        });
    });

    // Ketik manual lat/lng -> peta pindah
    function manualLatLng() {
        if (syncing || !pInit) return;
        var lat = parseFloat(fLat.value), lng = parseFloat(fLng.value);
        if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) return;
        setPoint(lat, lng, true);
    }
    fLat.addEventListener('change', manualLatLng);
    fLng.addEventListener('change', manualLatLng);

    // ---------------- Pencarian lokasi (Nominatim, gratis tanpa API key) ----------------
    var searchInput = document.getElementById('odpSearch');
    var searchResults = document.getElementById('odpSearchResults');
    var searchDebounce = null, searchAbort = null;

    function hideResults() { searchResults.style.display = 'none'; searchResults.innerHTML = ''; }

    function renderResults(items) {
        searchResults.innerHTML = '';
        if (!items.length) { hideResults(); return; }
        items.forEach(function (item) {
            var el = document.createElement('button');
            el.type = 'button';
            el.className = 'list-group-item list-group-item-action';
            el.textContent = item.display_name;
            el.addEventListener('click', function () {
                setPoint(parseFloat(item.lat), parseFloat(item.lon), true);
                searchInput.value = item.display_name;
                if (!fNama.value) fNama.value = item.display_name.split(',')[0];
                hideResults();
            });
            searchResults.appendChild(el);
        });
        searchResults.style.display = 'block';
    }

    function doSearch(q) {
        if (searchAbort) searchAbort.abort();
        searchAbort = new AbortController();
        var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=6&countrycodes=id&accept-language=id&q=' + encodeURIComponent(q);
        fetch(url, { signal: searchAbort.signal, headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) { renderResults(Array.isArray(d) ? d : []); })
            .catch(function (e) { if (e.name !== 'AbortError') hideResults(); });
    }

    searchInput.addEventListener('input', function () {
        var q = searchInput.value.trim();
        clearTimeout(searchDebounce);
        if (q.length < 3) { hideResults(); return; }
        searchDebounce = setTimeout(function () { doSearch(q); }, 500);
    });
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); clearTimeout(searchDebounce); var q = searchInput.value.trim(); if (q.length >= 3) doSearch(q); }
        else if (e.key === 'Escape') hideResults();
    });
    document.addEventListener('click', function (e) {
        if (e.target !== searchInput && !searchResults.contains(e.target)) hideResults();
    });
})();
</script>
<script>
// Konfirmasi hapus ODP via SweetAlert2 (bukan window.confirm bawaan browser).
document.addEventListener('click', function (e) {
    var link = e.target.closest('a.swal-delete');
    if (!link) return;
    e.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: link.dataset.text || 'Data akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#f46a6a',
        cancelButtonColor: '#74788d'
    }).then(function (result) {
        if (result.isConfirmed) window.location.href = link.href;
    });
});
</script>
@endsection
