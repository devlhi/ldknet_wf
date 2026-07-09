@extends('admin.layout')

@section('css')
<style>
    .modem-page {
        --md-ink: #0f172a;
        --md-slate: #64748b;
        --md-mut: #94a3b8;
        --md-line: #e7ebf3;
        --md-blue: #2563eb;
        --md-green: #10b981;
        --md-amber: #f59e0b;
        --md-mono: ui-monospace, 'SF Mono', 'JetBrains Mono', 'Cascadia Code', Consolas, monospace;
    }

    .modem-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 54%, #0891b2 100%);
        border: 0;
        border-radius: 20px;
        color: #fff;
        margin-bottom: 18px;
        overflow: hidden;
        position: relative;
    }

    .modem-hero::before,
    .modem-hero::after {
        border-radius: 999px;
        content: '';
        pointer-events: none;
        position: absolute;
    }

    .modem-hero::before {
        background: rgba(255, 255, 255, .11);
        height: 220px;
        right: -80px;
        top: -90px;
        width: 220px;
    }

    .modem-hero::after {
        background: rgba(255, 255, 255, .08);
        bottom: -120px;
        height: 260px;
        left: 34%;
        width: 260px;
    }

    .modem-hero__body {
        padding: 28px;
        position: relative;
        z-index: 1;
    }

    .modem-hero__eyebrow {
        align-items: center;
        color: rgba(255,255,255,.72);
        display: flex;
        font-size: 12px;
        font-weight: 700;
        gap: 8px;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .modem-hero__title {
        font-size: clamp(28px, 4vw, 42px);
        font-weight: 800;
        letter-spacing: -0.04em;
        margin: 10px 0 4px;
        word-break: break-word;
    }

    .modem-hero__sub {
        color: rgba(255,255,255,.78);
        font-size: 14px;
        margin: 0;
    }

    .modem-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .modem-action {
        align-items: center;
        backdrop-filter: blur(8px);
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 11px;
        color: #fff;
        display: inline-flex;
        font-weight: 700;
        gap: 8px;
        padding: 10px 14px;
        transition: background .15s ease, transform .15s ease;
    }

    .modem-action:hover {
        background: rgba(255,255,255,.22);
        color: #fff;
        transform: translateY(-1px);
    }

    .modem-action--light {
        background: #fff;
        color: #1e293b;
    }

    .modem-action--light:hover { color: #1e293b; background: #f8fafc; }

    .modem-metrics {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(3, 1fr);
        margin-top: 24px;
    }

    @media (max-width: 767px) { .modem-metrics { grid-template-columns: 1fr; } .modem-actions { justify-content: flex-start; margin-top: 18px; } }

    .modem-metric {
        background: rgba(255,255,255,.13);
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 14px;
        padding: 14px 16px;
    }

    .modem-metric__k {
        color: rgba(255,255,255,.68);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .modem-metric__v {
        color: #fff;
        font-family: var(--md-mono);
        font-size: 18px;
        font-weight: 700;
        margin-top: 6px;
    }

    .modem-panel {
        background: #fff;
        border: 1px solid var(--md-line);
        border-radius: 18px;
        overflow: hidden;
    }

    .modem-panel__head {
        align-items: center;
        border-bottom: 1px solid var(--md-line);
        display: flex;
        justify-content: space-between;
        padding: 18px 20px;
    }

    .modem-panel__title {
        color: var(--md-ink);
        font-size: 16px;
        font-weight: 800;
        margin: 0;
    }

    .modem-panel__hint {
        color: var(--md-mut);
        font-size: 12px;
        margin: 2px 0 0;
    }

    .modem-client-count {
        background: #eff6ff;
        border-radius: 999px;
        color: var(--md-blue);
        font-family: var(--md-mono);
        font-size: 12px;
        font-weight: 800;
        padding: 6px 12px;
    }

    .modem-table {
        margin: 0;
    }

    .modem-table thead th {
        background: #f8fafc;
        border-bottom: 1px solid var(--md-line);
        color: var(--md-slate);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        padding: 12px 20px;
        text-transform: uppercase;
    }

    .modem-table tbody td {
        border-color: #f1f5f9;
        color: var(--md-ink);
        padding: 14px 20px;
        vertical-align: middle;
    }

    .modem-host {
        align-items: center;
        display: flex;
        gap: 10px;
    }

    .modem-host__ico {
        align-items: center;
        background: #f1f5f9;
        border-radius: 10px;
        color: #475569;
        display: inline-flex;
        height: 36px;
        justify-content: center;
        width: 36px;
    }

    .modem-host__name {
        font-weight: 700;
    }

    .modem-mono {
        color: #334155;
        font-family: var(--md-mono);
        font-size: 12.5px;
    }

    .modem-empty {
        color: var(--md-mut);
        padding: 48px 20px;
        text-align: center;
    }

    .modem-empty i {
        color: #cbd5e1;
        display: block;
        font-size: 44px;
        margin-bottom: 10px;
    }

    .modem-modal .modal-dialog { max-width: 480px; }
    .modem-modal .modal-content {
        background: #fff !important;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
        overflow: hidden;
    }
    .modem-modal .modal-header {
        background: #fff;
        border-bottom: 1px solid #eef2f7;
        padding: 16px 20px;
    }
    .modem-modal .modal-title {
        align-items: center;
        color: var(--md-ink);
        display: flex;
        font-size: 16px;
        font-weight: 800;
        gap: 8px;
    }
    .modem-modal .modal-title .mdi {
        align-items: center;
        background: #eff6ff;
        border-radius: 8px;
        color: var(--md-blue);
        display: inline-flex;
        height: 24px;
        justify-content: center;
        width: 24px;
    }
    .modem-modal .modal-body { padding: 20px; }
    .modem-modal .form-label {
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .modem-modal .form-control:focus {
        border-color: var(--md-blue);
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .modem-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #eef2f7;
        padding: 14px 20px;
    }
</style>
@endsection

@section('content')
@php
    $clients = [];
    foreach ($clientInfoList as $clientHtml) {
        $parts = preg_split('/- Perangkat \d+:<br\/>/i', $clientHtml, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            $plain = str_replace(['<br/>', '<br />', '<br>'], "\n", $part);
            $host = 'Unknown';
            $ip = 'N/A';
            $mac = 'N/A';
            if (preg_match('/Hostname:\s*(.+)/i', $plain, $m)) { $host = trim($m[1]); }
            if (preg_match('/IP:\s*(.+)/i', $plain, $m)) { $ip = trim($m[1]); }
            if (preg_match('/MAC:\s*(.+)/i', $plain, $m)) { $mac = trim($m[1]); }
            $clients[] = ['host' => $host, 'ip' => $ip, 'mac' => $mac];
        }
    }
@endphp

<div class="page-content">
    <div class="container-fluid modem-page">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Akses Modem</h4>
                </div>
            </div>
        </div>

        <div class="modem-hero">
            <div class="modem-hero__body">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <div class="modem-hero__eyebrow"><i class="uil-wifi"></i> GenieACS device control</div>
                        <div class="modem-hero__title">{{ $ssid ?: 'SSID belum terbaca' }}</div>
                        <p class="modem-hero__sub">Kelola nama WiFi, kata sandi, dan pantau perangkat yang tersambung ke modem.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="modem-actions">
                            <a href="{{ url('server/acs/connect/' . $idrouter) }}" class="modem-action"><i class="uil-history"></i> Kembali</a>
                            <button class="modem-action modem-action--light" type="button" data-bs-toggle="modal" data-bs-target="#ssidModal"><i class="uil-edit"></i> Ganti SSID</button>
                            <button class="modem-action modem-action--light" type="button" data-bs-toggle="modal" data-bs-target="#passwordModal"><i class="uil-lock"></i> Ganti Password</button>
                        </div>
                    </div>
                </div>

                <div class="modem-metrics">
                    <div class="modem-metric">
                        <div class="modem-metric__k">Device ID</div>
                        <div class="modem-metric__v" title="{{ $id ?? '' }}">{{ \Illuminate\Support\Str::limit($id ?? 'N/A', 24) }}</div>
                    </div>
                    <div class="modem-metric">
                        <div class="modem-metric__k">Uptime</div>
                        <div class="modem-metric__v">{{ $deviceUptime ?: 'N/A' }}</div>
                    </div>
                    <div class="modem-metric">
                        <div class="modem-metric__k">Connected Clients</div>
                        <div class="modem-metric__v">{{ count($clients) }} perangkat</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modem-panel">
            <div class="modem-panel__head">
                <div>
                    <h5 class="modem-panel__title">Perangkat Terhubung</h5>
                    <p class="modem-panel__hint">Daftar host yang aktif/tercatat di modem pelanggan</p>
                </div>
                <span class="modem-client-count">{{ count($clients) }}</span>
            </div>

            @if (count($clients))
                <div class="table-responsive">
                    <table class="table modem-table">
                        <thead>
                            <tr>
                                <th>Hostname</th>
                                <th>IP Address</th>
                                <th>MAC Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clients as $client)
                                <tr>
                                    <td>
                                        <div class="modem-host">
                                            <span class="modem-host__ico"><i class="uil-desktop"></i></span>
                                            <span class="modem-host__name">{{ $client['host'] }}</span>
                                        </div>
                                    </td>
                                    <td><span class="modem-mono">{{ $client['ip'] }}</span></td>
                                    <td><span class="modem-mono">{{ $client['mac'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="modem-empty">
                    <i class="uil-wifi-slash"></i>
                    Belum ada perangkat yang terdeteksi tersambung ke modem ini.
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade modem-modal" id="ssidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ url('server/acs/device/change/ssid') }}" method="POST">
                @csrf
                <input type="hidden" name="deviceId" value="{{ $id ?? '' }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-wifi"></i> Ganti Nama WiFi</h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nama WiFi Baru</label>
                    <input type="text" name="ssid" class="form-control" placeholder="Masukan nama WiFi yang baru" value="{{ $ssid }}" required>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modem-modal" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ url('server/acs/device/change/password') }}" method="POST">
                @csrf
                <input type="hidden" name="deviceId" value="{{ $id ?? '' }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-lock-outline"></i> Ganti Kata Sandi WiFi</h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Kata Sandi Baru</label>
                    <input type="text" name="newpassword" class="form-control" placeholder="Minimal 8 karakter" minlength="8" required>
                    <small class="text-muted">Password minimal 8 karakter.</small>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
