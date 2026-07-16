@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0"><i class="uil uil-setting me-1"></i> Pengaturan SLA - {{ $device->nama }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('admin/nms/sla/settings/'.$device->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Metode Pengecekan SLA</label>
                                <select name="check_type" class="form-select" id="check_type">
                                    <option value="ping" {{ ($setting?->check_type ?? 'ping') === 'ping' ? 'selected' : '' }}>Ping (Status IP Device)</option>
                                    <option value="interface" {{ $setting?->check_type === 'interface' ? 'selected' : '' }}>Interface Tertentu (Link Status)</option>
                                </select>
                                <small class="text-muted">Pilih "Ping" untuk menghitung SLA berdasarkan status koneksi IP device (TCP port check). Pilih "Interface" untuk menghitung SLA berdasarkan status link port/interface tertentu.</small>
                            </div>

                            <div class="mb-3" id="interface-field" style="display: none;">
                                <label class="form-label fw-bold">Nama Interface / Port</label>
                                <select name="interface_name[]" class="form-select" id="interface_name" multiple style="min-height: 120px;">
                                    <option value="">-- Memuat interface dari device... --</option>
                                </select>
                                <small class="text-muted">Pilih satu atau lebih interface dari daftar yang terdeteksi pada device (tahan CTRL untuk memilih banyak).</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Target SLA (%)</label>
                                <input type="number" name="target_sla" class="form-control" value="{{ $setting?->target_sla ?? '99.50' }}" step="0.01" min="0" max="100">
                                <small class="text-muted">Target SLA yang diharapkan (contoh: 99.50% = maksimal 3.6 jam downtime per bulan).</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enabled" id="enabled" {{ ($setting?->enabled ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabled">Aktifkan SLA Monitoring untuk device ini</label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="uil uil-save"></i> Simpan Pengaturan</button>
                                <a href="{{ url('admin/nms/sla') }}" class="btn btn-secondary"><i class="uil uil-arrow-left"></i> Kembali</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h6><i class="uil uil-info-circle"></i> Informasi SLA</h6>
                        <table class="table table-sm table-bordered">
                            <tr><th>Nama Device</th><td>{{ $device->nama }}</td></tr>
                            <tr><th>IP</th><td>{{ $device->ip }}</td></tr>
                            <tr><th>Tipe</th><td>{{ strtoupper($device->tipe) }}</td></tr>
                            <tr><th>Status</th><td>{{ $device->status }}</td></tr>
                        </table>
                        <div class="alert alert-info mt-2">
                            <strong>Cara kerja SLA:</strong><br>
                            <strong>Ping:</strong> Setiap kali device dicek statusnya (via map atau polling), sistem mencatat apakah IP device merespons (up/down). SLA = (up / total) x 100%.<br>
                            <strong>Interface:</strong> Sistem menghitung jumlah status "up" dan "down" dari interface/port yang dipilih. Hanya port tersebut yang dihitung, bukan semua port.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var currentInterfaces = @json($setting?->interface_name ?? []);
var deviceId = "{{ $device->id }}";

function toggleInterfaceField() {
    var checkType = document.getElementById('check_type').value;
    var interfaceField = document.getElementById('interface-field');
    if (checkType === 'interface') {
        interfaceField.style.display = 'block';
        loadInterfaces();
    } else {
        interfaceField.style.display = 'none';
    }
}

function loadInterfaces() {
    var select = document.getElementById('interface_name');
    // Prevent re-fetch if already loaded
    if (select.dataset.loaded === '1') return;
    select.innerHTML = '<option value="">-- Memuat interface... --</option>';
    select.disabled = true;

    fetch('{{ url("admin/nms/device/poll") }}/' + deviceId + '?show_data=1')
        .then(r => r.json())
        .then(data => {
            var ports = [];
            if (data.ports && Array.isArray(data.ports)) {
                ports = data.ports.map(p => p.name).filter(n => n);
            }
            if (ports.length === 0) {
                select.innerHTML = '<option value="">-- Tidak ada interface terdeteksi --</option>';
            } else {
                select.innerHTML = '';
                ports.forEach(function(name) {
                    var opt = document.createElement('option');
                    opt.value = name;
                    opt.textContent = name;
                    if (currentInterfaces.includes(name)) opt.selected = true;
                    select.appendChild(opt);
                });
            }
            select.disabled = false;
            select.dataset.loaded = '1';
        })
        .catch(function() {
            select.innerHTML = '<option value="">-- Gagal memuat interface --</option>';
            select.disabled = false;
        });
}

document.getElementById('check_type').addEventListener('change', toggleInterfaceField);

// Trigger on load
toggleInterfaceField();
</script>
@endsection
