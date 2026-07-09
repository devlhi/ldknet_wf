@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0"><i class="uil uil-server me-1"></i> {{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        @if (session('auth_errors'))
                            <div class="alert alert-danger">
                                @foreach (session('auth_errors') as $err)
                                    {{ $err }}<br>
                                @endforeach
                            </div>
                        @endif

                        <form method="post" action="{{ $device ? url('admin/nms/device/update/'.$device->id) : url('admin/nms/device/add') }}">
                            @csrf
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Nama Device <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="nama" value="{{ old('nama', $device->nama ?? '') }}" required>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Tipe Device <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select name="tipe" id="tipeSelect" class="form-control" required>
                                        <option value="mikrotik" {{ old('tipe', $device->tipe ?? '') === 'mikrotik' ? 'selected' : '' }}>Mikrotik Router</option>
                                        <option value="crs" {{ old('tipe', $device->tipe ?? '') === 'crs' ? 'selected' : '' }}>Mikrotik CRS Switch</option>
                                        <option value="olt" {{ old('tipe', $device->tipe ?? '') === 'olt' ? 'selected' : '' }}>OLT</option>
                                        <option value="snmp" {{ old('tipe', $device->tipe ?? '') === 'snmp' ? 'selected' : '' }}>SNMP Generic</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">IP / Host <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="ip" value="{{ old('ip', $device->ip ?? '') }}" placeholder="192.168.1.1" required>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Port API</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="port" value="{{ old('port', $device->port ?? 8728) }}" id="portInput">
                                    <small class="text-muted">Mikrotik/CRS: 8728 (default) atau 8729 (SSL). OLT/SNMP: 161.</small>
                                </div>
                            </div>

                            <div id="mikrotikFields">
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Username</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="username" value="{{ old('username', $device->username ?? '') }}">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" name="password" placeholder="{{ $device ? 'Kosongkan jika tidak diubah' : '' }}">
                                    </div>
                                </div>
                            </div>

                            <div id="snmpFields" style="display:none;">
                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">SNMP Community</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="community" value="{{ old('community', $device->community ?? 'public') }}">
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <div class="alert alert-info mb-0 py-2">
                                            <i class="uil uil-info-circle"></i> OLT C-DATA polling via SNMP (FD-OLT-MIB). Mendukung semua tipe OLT C-DATA (EPON/GPON/Combo).
                                            Monitor: SFP DDM (Rx/Tx Power, Temperature, Voltage, TxBias), ONU online count, uplink port status.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Lokasi</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="lokasi" value="{{ old('lokasi', $device->lokasi ?? '') }}" placeholder="Contoh: Tower Pontianak">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Latitude</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="latitude" id="latInput" value="{{ old('latitude', $device->latitude ?? '') }}" placeholder="-0.123456">
                                </div>
                                <label class="col-sm-1 col-form-label">Longitude</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="longitude" id="lngInput" value="{{ old('longitude', $device->longitude ?? '') }}" placeholder="109.123456">
                                </div>
                            </div>

                            <div class="mb-3">
                                <div id="mapPicker" style="height:300px;width:100%;border-radius:8px;"></div>
                                <small class="text-muted">Klik peta untuk set koordinat.</small>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Status</label>
                                <div class="col-sm-9">
                                    <select name="status" class="form-control">
                                        <option value="active" {{ old('status', $device->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $device->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> {{ $device ? 'Update' : 'Simpan' }}</button>
                                    <a href="{{ url('admin/nms') }}" class="btn btn-secondary">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">Informasi</h5>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted">
                            <li><strong>Mikrotik/CRS</strong>: Monitor SFP rx/tx power, link status, resource via Mikrotik API.</li>
                            <li><strong>SNMP</strong>: Monitor interface status via SNMP walk (butuh PHP SNMP extension).</li>
                            <li><strong>OLT</strong>: Monitor C-DATA OLT via SNMP FD-OLT-MIB. SFP DDM, ONU count, uplink status. Semua tipe (EPON/GPON/Combo).</li>
                            <li>Password dienkripsi dengan <code>legacy_encrypt</code>.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('leaflet/leaflet.js') }}"></script>
<script>
var picker = L.map('mapPicker').setView([-0.12, 109.15], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(picker);

var marker = null;
var latInput = document.getElementById('latInput');
var lngInput = document.getElementById('lngInput');

function setMarker(lat, lng) {
    if (marker) picker.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(picker);
    latInput.value = lat.toFixed(6);
    lngInput.value = lng.toFixed(6);
}

picker.on('click', function(e) {
    setMarker(e.latlng.lat, e.latlng.lng);
});

if (latInput.value && lngInput.value) {
    var lat = parseFloat(latInput.value);
    var lng = parseFloat(lngInput.value);
    if (!isNaN(lat) && !isNaN(lng)) {
        marker = L.marker([lat, lng]).addTo(picker);
        picker.setView([lat, lng], 14);
    }
}

var tipeSelect = document.getElementById('tipeSelect');
var portInput = document.getElementById('portInput');
var mikrotikFields = document.getElementById('mikrotikFields');
var snmpFields = document.getElementById('snmpFields');

function toggleTipe() {
    var t = tipeSelect.value;
    var currentPort = parseInt(portInput.value);
    if (t === 'snmp' || t === 'olt') {
        mikrotikFields.style.display = 'none';
        snmpFields.style.display = 'block';
        if (currentPort === 8728 || currentPort === 8729 || !currentPort) {
            portInput.value = 161;
        }
    } else {
        mikrotikFields.style.display = 'block';
        snmpFields.style.display = 'none';
        if (currentPort === 161 || !currentPort) {
            portInput.value = 8728;
        }
    }
}

tipeSelect.addEventListener('change', toggleTipe);
toggleTipe();
</script>
@endsection
