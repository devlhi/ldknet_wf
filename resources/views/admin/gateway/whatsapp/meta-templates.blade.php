@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ $title }}</h4>

                        @if (session('auth_errors'))
                            <div class="alert alert-danger">
                                @foreach (session('auth_errors') as $err)
                                    <p class="mb-0">{{ $err }}</p>
                                @endforeach
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                @foreach (session('success') as $suc)
                                    <p class="mb-0">{{ $suc }}</p>
                                @endforeach
                            </div>
                        @endif

                        <p class="text-muted">Cek daftar template Meta langsung dari WhatsApp Cloud API. Pilih gateway Meta yang sudah disimpan atau isi WABA ID + Access Token manual di bawah ini.</p>

                        @if ($gateway)
                            @php
                                $metaSettings = \App\Support\WhatsAppGatewayResolver::metaSettings($gateway);
                                $metaTemplates = $metaSettings['templates'] ?? [];
                            @endphp
                            <div class="alert alert-info">
                                <strong>Mapping Template Notifikasi:</strong><br>
                                <ul class="mb-0">
                                    <li>Tagihan: <code>{{ $metaTemplates['tagihan'] ?? 'notif_tagihan' }}</code></li>
                                    <li>Reminder/Pengingat: <code>{{ $metaTemplates['pengingat'] ?? 'notif_pengingat' }}</code></li>
                                    <li>Tagihan Terbayar: <code>{{ $metaTemplates['terbayar'] ?? 'notif_tagihan_terbayar' }}</code></li>
                                    <li>Pelanggan Baru: <code>{{ $metaTemplates['pelanggan_baru'] ?? 'notif_daftar_berhasil' }}</code></li>
                                    <li>Isolir: <code>{{ $metaTemplates['isolir'] ?? 'notif_isolir' }}</code></li>
                                    <li>Buka Isolir: <code>{{ $metaTemplates['buka_isolir'] ?? 'notif_buka_isolir' }}</code></li>
                                </ul>
                                Bahasa: <code>{{ $metaSettings['language'] ?? 'id' }}</code> &middot; WABA ID: <code>{{ $metaSettings['waba_id'] ?? '-' }}</code>
                            </div>
                        @endif

                        <form method="get" class="row g-3 mb-4">
                            @if ($metaGateways->isNotEmpty())
                                <div class="col-md-6">
                                    <label class="form-label">Pilih Gateway Meta Tersimpan</label>
                                    <select name="gateway_id" class="form-control" onchange="this.form.submit()">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($metaGateways as $mg)
                                            <option value="{{ $mg->id }}" {{ ($gateway && $gateway->id === $mg->id) ? 'selected' : '' }}>
                                                {{ $mg->nama }} (ID: {{ $mg->id }}) - {{ strtoupper($mg->mode) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label">WhatsApp Business Account ID (WABA ID)</label>
                                <input type="text" name="business_account_id" class="form-control" value="{{ $businessAccountId ?? '' }}" placeholder="Contoh: 1234567890">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Graph URL</label>
                                <input type="text" name="graph_url" class="form-control" value="{{ $graphUrl ?? 'https://graph.facebook.com/v20.0' }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Access Token (opsional, isi manual untuk cek tanpa gateway)</label>
                                <input type="text" name="access_token" class="form-control" placeholder="Tempel Meta System User Access Token">
                                @if ($hasAccessToken)
                                    <small class="text-success">Token dari gateway tersimpan sudah dipakai. Kosongkan untuk memakai token gateway.</small>
                                @else
                                    <small class="text-warning">Belum ada token. Isi manual untuk cek template sekarang.</small>
                                @endif
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><i class="uil uil-search"></i> Cek Template</button>
                                @if ($gateway)
                                    <a href="{{ url('admin/whatsapp/edit/'.$gateway->id) }}" class="btn btn-warning"><i class="uil uil-edit"></i> Edit Gateway</a>
                                @endif
                            </div>
                        </form>

                        <div class="card border mb-4">
                            <div class="card-body">
                                <h5>Template Meta Default Aplikasi</h5>
                                <p class="text-muted mb-3">Template ini sudah tersedia di aplikasi. Koneksi ke Meta hanya dibutuhkan saat mendaftarkan template ke WhatsApp Cloud API.</p>
                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nama Template</th>
                                                <th>Bahasa</th>
                                                <th>Kategori</th>
                                                <th>Variabel</th>
                                                <th>Isi Pesan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($localTemplates as $tpl)
                                                <tr>
                                                    <td><code>{{ $tpl['name'] }}</code></td>
                                                    <td>{{ $tpl['language'] }}</td>
                                                    <td>{{ $tpl['category'] }}</td>
                                                    <td>
                                                        @foreach ($tpl['parameters'] as $i => $param)
                                                            <button type="button" class="badge bg-secondary border-0 me-1 mb-1 btn-copy-var" data-copy="{{ $param }}" title="Klik untuk menyalin {{ $param }}">
                                                                <i class="mdi mdi-content-copy"></i> {{ $param }} <span class="text-white-50">#{{ $i + 1 }}</span>
                                                            </button>
                                                        @endforeach
                                                    </td>
                                                    <td><pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $tpl['body'] }}</pre></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <h5>Buat Template Default di Meta</h5>
                                <p class="text-muted mb-3">Tombol ini mendaftarkan {{ count($localTemplates) }} template default aplikasi ke Meta Developer. Setelah dibuat, tunggu status APPROVED dari Meta.</p>
                                <form method="post" action="{{ url('admin/whatsapp/meta/create-templates') }}" id="createTemplatesForm">
                                    @csrf
                                    <input type="hidden" name="gateway_id" value="{{ $gateway->id ?? '' }}">
                                    <input type="hidden" name="business_account_id" value="{{ $businessAccountId ?? '' }}">
                                    <input type="hidden" name="graph_url" value="{{ $graphUrl ?? 'https://graph.facebook.com/v20.0' }}">
                                    <input type="hidden" name="language" value="{{ $metaSettings['language'] ?? 'id' }}">
                                    <div class="mb-3">
                                        <label class="form-label">Access Token Manual (isi jika belum memilih gateway Meta)</label>
                                        <input type="text" name="access_token" class="form-control" placeholder="Tempel token jika belum tersimpan di gateway">
                                    </div>
                                    <button type="submit" class="btn btn-success" id="btnCreateTemplates">
                                        <i class="uil uil-plus"></i> Buat Template Default di Meta
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Template</th>
                                        <th>Bahasa</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Komponen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($templates as $tpl)
                                        <tr>
                                            <td>{{ $tpl['name'] ?? '-' }}</td>
                                            <td>{{ $tpl['language'] ?? '-' }}</td>
                                            <td>{{ $tpl['category'] ?? '-' }}</td>
                                            <td>
                                                @if (($tpl['status'] ?? '') === 'APPROVED')
                                                    <span class="badge bg-success">APPROVED</span>
                                                @elseif (($tpl['status'] ?? '') === 'PENDING')
                                                    <span class="badge bg-warning">PENDING</span>
                                                @elseif (($tpl['status'] ?? '') === 'REJECTED')
                                                    <span class="badge bg-danger">REJECTED</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $tpl['status'] ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @foreach ($tpl['components'] ?? [] as $comp)
                                                    <span class="badge bg-info me-1">{{ $comp['type'] ?? '' }}</span>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">
                                            @if ($businessAccountId && $hasAccessToken)
                                                Tidak ada template ditemukan, atau WABA ID / Access Token salah.
                                            @else
                                                Isi WABA ID dan Access Token (atau pilih gateway Meta) lalu klik Cek Template.
                                            @endif
                                        </td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <hr>
                        <h5>Kirim Test Pesan Meta</h5>
                        <form method="post" action="{{ url('admin/whatsapp/meta/test') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nomor Tujuan (format 62...)</label>
                                <input type="text" name="number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pesan Text (test)</label>
                                <textarea name="message" class="form-control" rows="3" required>Test WhatsApp Meta Official dari ANNORTY NET</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Kirim Test</button>
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
function copyToClipboard(text, label) {
    const done = () => {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: (label || 'Teks') + ' disalin',
                showConfirmButton: false,
                timer: 1500
            });
        }
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
    } else {
        fallbackCopy(text, done);
    }
}

function fallbackCopy(text, done) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try { document.execCommand('copy'); } catch (err) {}
    document.body.removeChild(ta);
    if (done) done();
}

document.querySelectorAll('.btn-copy-var').forEach(function(btn) {
    btn.addEventListener('click', function() {
        copyToClipboard(this.dataset.copy, this.dataset.copy);
    });
});

document.querySelectorAll('.btn-copy-body').forEach(function(btn) {
    btn.addEventListener('click', function() {
        copyToClipboard(this.dataset.copy, 'Isi pesan');
    });
});

document.getElementById('btnCreateTemplates').addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Buat Template Default?',
        text: 'Ini akan membuat template default aplikasi, termasuk tagihan, reminder, terbayar, pelanggan baru, isolir, dan buka isolir ke Meta.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Buat',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#34c38f',
        cancelButtonColor: '#f46a6a'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('createTemplatesForm').submit();
        }
    });
});
</script>
@endsection
