@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0"><i class="uil uil-globe me-1"></i> Pengaturan Global SLA</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ url('admin/nms/sla/settings/global') }}" method="POST">
                            @csrf
                            <div class="alert alert-warning">
                                <i class="uil uil-exclamation-triangle"></i> Pengaturan ini akan <strong>menimpa (overwrite)</strong> konfigurasi SLA dari device yang dipilih. Jika Anda memilih metode "Interface", sistem akan tetap mempertahankan nama interface sebelumnya jika sudah pernah diatur secara spesifik.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Metode Pengecekan SLA Default</label>
                                <select name="check_type" class="form-select" id="check_type">
                                    <option value="ping">Ping (Status IP Device)</option>
                                    <option value="interface">Interface Tertentu (Link Status)</option>
                                </select>
                                <small class="text-muted">Metode standar yang akan diterapkan pada semua device yang dipilih.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Target SLA Default (%)</label>
                                <input type="number" name="target_sla" class="form-control" value="99.50" step="0.01" min="0" max="100">
                                <small class="text-muted">Target SLA yang diharapkan untuk semua device terpilih (contoh: 99.50% = maksimal 3.6 jam downtime per bulan).</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enabled" id="enabled" checked>
                                    <label class="form-check-label" for="enabled">Aktifkan SLA Monitoring untuk device yang dipilih</label>
                                </div>
                            </div>

                            <div class="mb-3" id="interface-column-info" style="display:none;">
                                <div class="alert alert-info">
                                    <i class="uil uil-info-circle"></i> Pilih interface untuk <strong>masing-masing device</strong> di kolom "Interface" pada tabel di bawah. Klik tombol "Muat Interface" di setiap baris untuk mengambil daftar port dari device.
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3 border-bottom pb-2">Terapkan ke Device Berikut:</h5>
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="selectAll">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="deselectAll">Batalkan Pilihan</button>
                                @if (old('check_type') === 'interface')
                                <button type="button" class="btn btn-sm btn-outline-info mb-2" id="loadAllInterfaces">Muat Semua Interface</button>
                                @endif
                                
                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered table-hover">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">#</th>
                                                <th>Nama Device</th>
                                                <th>IP</th>
                                                <th>Tipe</th>
                                                <th style="width: 220px;">Interface</th>
                                                <th>Setting Saat Ini</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($devices as $device)
                                                @php
                                                    $setting = $existingSettings->get($device->id);
                                                    $currentSetting = "Default (Ping)";
                                                    if ($setting) {
                                                        $ifaceDisplay = '-';
                                                        if ($setting->interface_name) {
                                                            $ifaces = is_array($setting->interface_name) ? $setting->interface_name : [$setting->interface_name];
                                                            $ifaceDisplay = implode(', ', $ifaces);
                                                        }
                                                        $currentSetting = $setting->check_type === 'ping' 
                                                            ? 'Ping ('.$setting->target_sla.'%)' 
                                                            : 'Interface: '.$ifaceDisplay.' ('.$setting->target_sla.'%)';
                                                        if (!$setting->enabled) {
                                                            $currentSetting .= " - Nonaktif";
                                                        }
                                                    }
                                                @endphp
                                                <tr data-device-id="{{ $device->id }}">
                                                    <td class="text-center">
                                                        <input class="form-check-input device-checkbox" type="checkbox" name="devices[]" value="{{ $device->id }}" checked>
                                                    </td>
                                                    <td>{{ $device->nama }}</td>
                                                    <td>{{ $device->ip }}</td>
                                                    <td>{{ strtoupper($device->tipe) }}</td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <select name="interface_name[{{ $device->id }}][]" class="form-select form-select-sm interface-select" data-device-id="{{ $device->id }}" multiple style="min-height: 80px;" disabled>
                                                                <option value="">- Ping Mode -</option>
                                                            </select>
                                                            <button type="button" class="btn btn-outline-secondary btn-sm load-iface-btn" data-device-id="{{ $device->id }}" style="display:none;">
                                                                <i class="uil uil-refresh"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted small">{{ $currentSetting }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Apakah Anda yakin ingin menerapkan pengaturan ini ke semua device yang dipilih?')"><i class="uil uil-save"></i> Terapkan Pengaturan Global</button>
                                <a href="{{ url('admin/nms/sla') }}" class="btn btn-secondary"><i class="uil uil-arrow-left"></i> Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('selectAll').addEventListener('click', function() {
    document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = true);
});

document.getElementById('deselectAll').addEventListener('click', function() {
    document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = false);
});

document.getElementById('check_type').addEventListener('change', function() {
    var checkType = this.value;
    var info = document.getElementById('interface-column-info');
    var loadBtns = document.querySelectorAll('.load-iface-btn');
    var selects = document.querySelectorAll('.interface-select');

    if (checkType === 'interface') {
        info.style.display = 'block';
        loadBtns.forEach(btn => btn.style.display = 'block');
        selects.forEach(sel => {
            if (sel.options.length <= 1) {
                sel.innerHTML = '<option value="">- Klik tombol load -</option>';
            }
            sel.disabled = false;
        });
        
        // Ensure "Load All" button exists if not dynamically added
        if (!document.getElementById('loadAllInterfaces')) {
            var loadAllBtn = document.createElement('button');
            loadAllBtn.type = 'button';
            loadAllBtn.className = 'btn btn-sm btn-outline-info mb-2 ms-1';
            loadAllBtn.id = 'loadAllInterfaces';
            loadAllBtn.textContent = 'Muat Semua Interface';
            loadAllBtn.addEventListener('click', loadAllDeviceInterfaces);
            document.getElementById('deselectAll').insertAdjacentElement('afterend', loadAllBtn);
        }
    } else {
        info.style.display = 'none';
        loadBtns.forEach(btn => btn.style.display = 'none');
        selects.forEach(sel => {
            sel.innerHTML = '<option value="">- Ping Mode -</option>';
            sel.disabled = true;
            sel.dataset.loaded = '0';
        });
        var loadAllBtn = document.getElementById('loadAllInterfaces');
        if (loadAllBtn) loadAllBtn.remove();
    }
});

function loadDeviceInterfaces(deviceId, btnElement, selectElement) {
    if (selectElement.dataset.loaded === '1') return;
    
    var originalHtml = btnElement.innerHTML;
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    btnElement.disabled = true;
    selectElement.disabled = true;
    selectElement.innerHTML = '<option value="">Memuat...</option>';

    fetch('{{ url("admin/nms/device/poll") }}/' + deviceId + '?show_data=1')
        .then(r => r.json())
        .then(data => {
            var ports = [];
            if (data.ports && Array.isArray(data.ports)) {
                ports = data.ports.map(p => p.name).filter(n => n);
            }
            if (ports.length === 0) {
                selectElement.innerHTML = '<option value="" disabled>Tidak ada interface</option>';
            } else {
                selectElement.innerHTML = '';
                ports.forEach(function(name) {
                    var opt = document.createElement('option');
                    opt.value = name;
                    opt.textContent = name;
                    selectElement.appendChild(opt);
                });
            }
            selectElement.disabled = false;
            selectElement.dataset.loaded = '1';
        })
        .catch(function() {
            selectElement.innerHTML = '<option value="">Gagal memuat</option>';
            selectElement.disabled = false;
        })
        .finally(function() {
            btnElement.innerHTML = originalHtml;
            btnElement.disabled = false;
        });
}

function loadAllDeviceInterfaces() {
    // Find all rows where checkbox is checked
    document.querySelectorAll('.device-checkbox:checked').forEach(cb => {
        var row = cb.closest('tr');
        var deviceId = cb.value;
        var btn = row.querySelector('.load-iface-btn');
        var select = row.querySelector('.interface-select');
        
        if (select.dataset.loaded !== '1') {
            loadDeviceInterfaces(deviceId, btn, select);
        }
    });
}

// Bind load buttons
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.load-iface-btn');
    if (btn) {
        var deviceId = btn.dataset.deviceId;
        var select = document.querySelector('.interface-select[data-device-id="' + deviceId + '"]');
        loadDeviceInterfaces(deviceId, btn, select);
    }
});

// Trigger on load
document.getElementById('check_type').dispatchEvent(new Event('change'));
</script>
@endsection
