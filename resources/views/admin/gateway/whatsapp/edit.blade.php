@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        @foreach ($content as $row)
            <div class="row">
                <div class="col-md-12">
                    @if (session('auth_errors'))
                        <div class="alert alert-danger alert-message" role="alert">
                            @foreach (session('auth_errors') as $err)
                                {{ $err }}
                            @endforeach
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                            @foreach (session('success') as $suc)
                                {{ $suc }}
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
                        </div>
                    @endif
                    <div class="card overflow-hidden">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title"><i class="uil uil-whatsapp"></i> Whatsapp Gateway</h3>
                        </div>
                        <div class="card-body">
                            <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/whatsapp/update/number') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $row->id }}">
                                <input type="hidden" name="type" value="{{ $row->type }}">
                                @php
                                    $isMeta = str_contains(strtolower((string) $row->nama), 'meta') || str_contains((string) $row->api_url, 'graph.facebook.com') || str_starts_with((string) $row->api_url, 'meta###');
                                    $metaSettings = \App\Support\WhatsAppGatewayResolver::metaSettings($row);
                                    $metaTemplates = $metaSettings['templates'] ?? [];
                                @endphp

                                <div class="alert alert-info">
                                    <strong>Webhook Meta Production:</strong>
                                    <code>{{ url('webhook/whatsapp/meta') }}</code><br>
                                    <strong>Verify Token:</strong>
                                    <code>{{ $metaSettings['verify_token'] ?? '' }}</code>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Provider WhatsApp</label>
                                    <select name="provider" id="providerSelect" class="form-control">
                                        <option value="wablas" {{ ! $isMeta ? 'selected' : '' }}>Gateway Lama / Wablas Compatible</option>
                                        <option value="meta" {{ $isMeta ? 'selected' : '' }}>WhatsApp Meta Official</option>
                                    </select>
                                    <small class="text-muted">Pilih salah satu. Jika mode ON, provider lain dengan type yang sama akan otomatis OFF.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nama Gateway</label>
                                    <input type="text" class="form-control" name="nama" value="{{ $row->nama }}">
                                </div>

                                <div class="mb-3">
                                    <label for="merchantcode" class="col-sm-2 col-form-label">Mode</label>
                                    <div class="square-switch">
                                        <input type="checkbox" name="mode" id="square-switch1" switch="none" value="on" {{ $row->mode === 'on' ? 'checked' : '' }} />
                                        <label for="square-switch1" data-on-label="On" data-off-label="Off"></label>
                                    </div>
                                </div>

                                <div id="formContainer" style="display:{{ $row->mode === 'on' ? 'block' : 'none' }};">

                                    <div class="mb-3">
                                        <label for="apikey" class="form-label">API Url {{ $isMeta ? '(Meta Graph)' : 'Whatsapp Gateway' }}</label>
                                        <input type="text" class="form-control" name="api_url" value="{{ $isMeta ? ($metaSettings['graph_url'] ?? 'https://graph.facebook.com/v20.0') : $row->api_url }}">
                                        <small class="text-meta-hint text-muted" style="display:none">Default: https://graph.facebook.com/v20.0</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="apikey" class="form-label">{{ $isMeta ? 'Access Token (Meta System User)' : 'API Key Whatsapp Gateway' }}</label>
                                        <input type="text" class="form-control" name="api_key" value="{{ $row->api_key }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="apikey" class="form-label">{{ $isMeta ? 'Phone Number ID (Meta)' : 'Whatsapp Number for sender' }}</label>
                                        <input type="text" class="form-control" id="senderInput" name="sender" value="{{ $isMeta ? ($metaSettings['phone_number_id'] ?? '') : $row->sender }}" required>
                                        <input type="hidden" id="metaPhoneNumberIdInput" name="meta_phone_number_id" value="{{ $metaSettings['phone_number_id'] ?? '' }}">
                                        <small class="text-meta-phone-hint text-muted" style="display:{{ $isMeta ? 'block' : 'none' }}">Phone Number ID Meta disimpan di setting Meta (kolom sender legacy hanya penanda).</small>
                                    </div>

                                    <div id="metaSettings" style="display:{{ $isMeta ? 'block' : 'none' }};">
                                        <hr>
                                        <h5>Pengaturan WhatsApp Meta Official</h5>
                                        <p class="text-muted">Semua setting di bawah disimpan di gateway ini, tidak perlu edit file <code>.env</code>.</p>

                                        <div class="mb-3">
                                            <label class="form-label">Meta Graph URL</label>
                                            <input type="text" class="form-control" name="meta_graph_url" value="{{ $metaSettings['graph_url'] ?? 'https://graph.facebook.com/v20.0' }}">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">WhatsApp Business Account ID (WABA ID)</label>
                                            <input type="text" class="form-control" name="meta_waba_id" value="{{ $metaSettings['waba_id'] ?? '' }}" placeholder="Untuk list template Meta">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Webhook Verify Token</label>
                                            <input type="text" class="form-control" name="meta_verify_token" value="{{ $metaSettings['verify_token'] ?? '' }}">
                                            <small class="text-muted">Gunakan token ini saat verifikasi webhook di Meta App dashboard.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Bahasa Template (default)</label>
                                            <input type="text" class="form-control" name="meta_language" value="{{ $metaSettings['language'] ?? 'id' }}">
                                        </div>

                                        <h6>Nama Template Meta (harus approved di Meta)</h6>
                                        <div class="mb-3">
                                            <label class="form-label">Template Notif Tagihan</label>
                                            <input type="text" class="form-control" name="meta_template_tagihan" value="{{ $metaTemplates['tagihan'] ?? 'notif_tagihan' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Template Notif Reminder/Pengingat</label>
                                            <input type="text" class="form-control" name="meta_template_pengingat" value="{{ $metaTemplates['pengingat'] ?? 'notif_pengingat' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Template Notif Tagihan Terbayar</label>
                                            <input type="text" class="form-control" name="meta_template_terbayar" value="{{ $metaTemplates['terbayar'] ?? 'notif_tagihan_terbayar' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Template Notif Pelanggan Baru</label>
                                            <input type="text" class="form-control" name="meta_template_pelanggan_baru" value="{{ $metaTemplates['pelanggan_baru'] ?? 'notif_daftar_berhasil' }}">
                                        </div>
                                    </div>

                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary waves-effect waves-light me-1">
                                        Update
                                    </button>
                                </div>

                            </form>

                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modeSwitch = document.getElementById('square-switch1');
    const formContainer = document.getElementById('formContainer');
    const providerSelect = document.getElementById('providerSelect');
    const metaHint = document.querySelector('.text-meta-hint');
    const metaSettings = document.getElementById('metaSettings');
    const senderInput = document.getElementById('senderInput');
    const metaPhoneNumberIdInput = document.getElementById('metaPhoneNumberIdInput');
    const metaPhoneHint = document.querySelector('.text-meta-phone-hint');

    modeSwitch.addEventListener('change', function() {
        if (modeSwitch.checked) {
            formContainer.style.display = 'block';
        } else {
            formContainer.style.display = 'none';
        }
    });

    // Initial state
    if (!modeSwitch.checked) {
        formContainer.style.display = 'none';
    }

    function toggleMetaHints() {
        const isMeta = providerSelect.value === 'meta';
        if (metaHint) metaHint.style.display = isMeta ? 'block' : 'none';
        if (metaSettings) metaSettings.style.display = isMeta ? 'block' : 'none';
        if (metaPhoneHint) metaPhoneHint.style.display = isMeta ? 'block' : 'none';
        if (isMeta && senderInput && metaPhoneNumberIdInput) {
            metaPhoneNumberIdInput.value = senderInput.value;
        }
    }
    if (senderInput) {
        senderInput.addEventListener('input', function() {
            if (providerSelect.value === 'meta' && metaPhoneNumberIdInput) {
                metaPhoneNumberIdInput.value = senderInput.value;
            }
        });
    }
    const editForm = providerSelect.closest('form');
    if (editForm) {
        editForm.addEventListener('submit', function() {
            if (providerSelect.value === 'meta' && senderInput && metaPhoneNumberIdInput) {
                metaPhoneNumberIdInput.value = senderInput.value;
            }
        });
    }
    providerSelect.addEventListener('change', toggleMetaHints);
    toggleMetaHints();
</script>
@endsection
