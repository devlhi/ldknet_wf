@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        @php
    $isMetaPrefill = ($prefillProvider ?? 'wablas') === 'meta';
@endphp
        <div class="row">
            <div class="col-md-8">
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
                        <div class="alert alert-info">
                            <i class="uil uil-info-circle"></i> Hanya satu WhatsApp gateway yang boleh aktif. Jika gateway ini diset ON, gateway lain otomatis OFF.
                        </div>
                        <form autocomplete="off" name="formadd" method="post" action="{{ url('admin/whatsapp/add/number') }}">
                            @csrf
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Provider</label>
                                <div class="col-sm-9">
                                    <select name="provider" id="providerSelect" class="form-control">
                                        <option value="wablas" {{ ! $isMetaPrefill ? 'selected' : '' }}>Gateway Lama / Wablas Compatible</option>
                                        <option value="meta" {{ $isMetaPrefill ? 'selected' : '' }}>WhatsApp Meta Official</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Nama Gateway</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="nama" value="{{ $isMetaPrefill ? 'Meta Official' : 'Whatsapp Gateway' }}">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Type</label>
                                <div class="col-sm-9">
                                    <select name="type" class="form-control">
                                        <option value="blast">Blast / Notifikasi</option>
                                        <option value="otp">OTP</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">API URL</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="api_url" id="apiUrlInput" value="{{ $isMetaPrefill ? 'https://graph.facebook.com/v20.0' : '' }}" placeholder="{{ $isMetaPrefill ? 'https://graph.facebook.com/v20.0' : 'URL gateway lama, contoh: https://botnew.autosend.my.id' }}" required>
                                    <input type="hidden" name="meta_graph_url" value="https://graph.facebook.com/v20.0">
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label" id="apiKeyLabel">{{ $isMetaPrefill ? 'Access Token (Meta)' : 'API Key' }}</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="api_key" placeholder="{{ $isMetaPrefill ? 'Tempel Meta System User Access Token' : 'Masukkan API key gateway' }}" required>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label" id="senderLabel">{{ $isMetaPrefill ? 'Phone Number ID (Meta)' : 'Nomor Sender' }}</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="sender" name="sender" placeholder="{{ $isMetaPrefill ? 'Phone Number ID dari Meta' : 'Nomor WA pengirim' }}" required>
                                    <input type="hidden" id="metaPhoneNumberIdInput" name="meta_phone_number_id" value="">
                                    <small class="text-meta-phone-hint text-muted" style="display:none">Phone Number ID Meta disimpan di setting Meta, bukan kolom sender legacy.</small>
                                </div>
                            </div>

                            <div id="metaOnlyFields" style="display:{{ $isMetaPrefill ? 'block' : 'none' }};">
                                <hr>
                                <h6 class="text-primary">Pengaturan Khusus Meta Official</h6>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">WABA ID</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_waba_id" placeholder="WhatsApp Business Account ID">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Verify Token</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="metaVerifyToken" name="meta_verify_token" value="landaknet-meta-webhook">
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyMetaField('metaVerifyToken', this)"><i class="uil uil-copy"></i> Salin</button>
                                        </div>
                                        <small class="text-muted">Token ini dipakai saat verifikasi webhook di Meta App Dashboard (kolom "Verifikasi token"). Harus sama persis.</small>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">URL Callback Webhook</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="metaWebhookUrl" value="{{ url('webhook/whatsapp/meta') }}" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyMetaField('metaWebhookUrl', this)"><i class="uil uil-copy"></i> Salin</button>
                                        </div>
                                        <small class="text-muted">Tempel ke kolom "URL Callback" di Meta App Dashboard &rarr; WhatsApp &rarr; Konfigurasi Webhooks. Wajib HTTPS publik — jika masih localhost, deploy dulu atau pakai ngrok.</small>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Bahasa Template</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_language" value="id">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Template Tagihan</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_template_tagihan" value="notif_tagihan">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Template Reminder</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_template_pengingat" value="notif_pengingat">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Template Terbayar</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_template_terbayar" value="notif_tagihan_terbayar">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Template Pelanggan Baru</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_template_pelanggan_baru" value="notif_daftar_berhasil">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Template Isolir</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_template_isolir" value="notif_isolir">
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Template Buka Isolir</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="meta_template_buka_isolir" value="notif_buka_isolir">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Mode</label>
                                <div class="col-sm-9">
                                    <select name="mode" class="form-control">
                                        <option value="off">OFF</option>
                                        <option value="on">ON</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label for="buttonadd" class="col-sm-2 col-form-label"></label>
                                <div class="col-sm-10">
                                    <button class="btn btn-primary" id="buttonadd" type="submit"><i class="uil uil-edit"></i> Submit</button>
                                </div>
                            </div>

                        </form>

                    </div>

                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Informasi</h5>
                    </div>

                    <div class="card-body">
                        <pre>
Mohon pada saat melakukan setup whatsapp ini, nomor sudah konek ke

<a href="https://gateway.acenet.id">Whatsapp Gateway</a> milik Mbs

                                                        </pre>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const providerSelect = document.getElementById('providerSelect');
    const form = providerSelect.closest('form');
    const metaOnlyFields = document.getElementById('metaOnlyFields');
    const apiUrlInput = document.getElementById('apiUrlInput');
    const apiKeyLabel = document.getElementById('apiKeyLabel');
    const senderLabel = document.getElementById('senderLabel');
    const senderInput = document.getElementById('sender');
    const metaPhoneNumberIdInput = document.getElementById('metaPhoneNumberIdInput');
    const metaPhoneHint = document.querySelector('.text-meta-phone-hint');

    function toggleProviderUi() {
        const isMeta = providerSelect.value === 'meta';
        metaOnlyFields.style.display = isMeta ? 'block' : 'none';
        apiKeyLabel.textContent = isMeta ? 'Access Token (Meta)' : 'API Key';
        senderLabel.textContent = isMeta ? 'Phone Number ID (Meta)' : 'Nomor Sender';
        apiUrlInput.placeholder = isMeta ? 'https://graph.facebook.com/v20.0' : 'URL gateway lama, contoh: https://botnew.autosend.my.id';
        senderInput.placeholder = isMeta ? 'Phone Number ID dari Meta' : 'Nomor WA pengirim';
        if (metaPhoneHint) metaPhoneHint.style.display = isMeta ? 'block' : 'none';
        if (metaPhoneNumberIdInput) metaPhoneNumberIdInput.value = isMeta ? senderInput.value : '';

        if (isMeta) {
            form.querySelector('input[name="nama"]').value = 'Meta Official';
            apiUrlInput.value = apiUrlInput.value || 'https://graph.facebook.com/v20.0';
        } else {
            form.querySelector('input[name="nama"]').value = 'Whatsapp Gateway';
            if (apiUrlInput.value === 'https://graph.facebook.com/v20.0') {
                apiUrlInput.value = '';
            }
        }
    }

    function copyMetaField(fieldId, button) {
        const field = document.getElementById(fieldId);
        if (! field) {
            return;
        }

        field.select();
        field.setSelectionRange(0, field.value.length);

        const afterCopy = () => {
            const oldHtml = button.innerHTML;
            button.innerHTML = '<i class="uil uil-check"></i> Tersalin';
            setTimeout(() => {
                button.innerHTML = oldHtml;
            }, 1500);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(field.value).then(afterCopy).catch(() => {
                document.execCommand('copy');
                afterCopy();
            });
        } else {
            document.execCommand('copy');
            afterCopy();
        }
    }

    senderInput.addEventListener('input', function() {
        if (providerSelect.value === 'meta' && metaPhoneNumberIdInput) {
            metaPhoneNumberIdInput.value = senderInput.value;
        }
    });

    form.addEventListener('submit', function() {
        if (providerSelect.value === 'meta' && metaPhoneNumberIdInput) {
            metaPhoneNumberIdInput.value = senderInput.value;
        }
    });

    providerSelect.addEventListener('change', toggleProviderUi);
    toggleProviderUi();
</script>
@endsection
