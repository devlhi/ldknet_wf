@extends('admin.layout')

@section('css')
<style>
    .acs-page {
        --acs-ink: #0f172a;
        --acs-slate: #64748b;
        --acs-mut: #94a3b8;
        --acs-line: #e7ebf3;
        --acs-blue: #2563eb;
        --acs-green: #10b981;
        --acs-red: #ef4444;
        --acs-amber: #f59e0b;
        --acs-mono: ui-monospace, 'SF Mono', 'JetBrains Mono', 'Cascadia Code', Consolas, monospace;
    }

    /* ---------- Hero / page head ---------- */
    .acs-head {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .acs-head__title {
        align-items: center;
        display: flex;
        gap: 12px;
    }

    .acs-head__mark {
        align-items: center;
        background: linear-gradient(140deg, #1e293b 0%, #2563eb 100%);
        border-radius: 13px;
        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.28);
        color: #fff;
        display: inline-flex;
        font-size: 22px;
        height: 46px;
        justify-content: center;
        width: 46px;
    }

    .acs-head h4 {
        color: var(--acs-ink);
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.1;
        margin: 0;
    }

    .acs-head__sub {
        color: var(--acs-mut);
        font-size: 12.5px;
        margin: 2px 0 0;
    }

    .acs-head__live {
        align-items: center;
        background: #fff;
        border: 1px solid var(--acs-line);
        border-radius: 999px;
        color: var(--acs-slate);
        display: inline-flex;
        font-size: 12px;
        font-weight: 600;
        gap: 7px;
        padding: 8px 14px;
    }

    .acs-pulse {
        background: var(--acs-green);
        border-radius: 999px;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.16);
        display: inline-block;
        height: 8px;
        width: 8px;
        animation: acsPulse 1.6s ease-in-out infinite;
    }

    @keyframes acsPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .5; transform: scale(.72); }
    }

    /* ---------- Stat tiles ---------- */
    .acs-stats {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(4, 1fr);
        margin-bottom: 20px;
    }

    @media (max-width: 991px) { .acs-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px) { .acs-stats { grid-template-columns: 1fr; } }

    .acs-stat {
        background: #fff;
        border: 1px solid var(--acs-line);
        border-radius: 16px;
        overflow: hidden;
        padding: 18px 18px 16px;
        position: relative;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .acs-stat:hover {
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        transform: translateY(-3px);
    }

    .acs-stat::after {
        border-radius: 3px;
        bottom: 0;
        content: '';
        height: 3px;
        left: 0;
        position: absolute;
        width: 100%;
    }

    .acs-stat--total::after { background: var(--acs-blue); }
    .acs-stat--online::after { background: var(--acs-green); }
    .acs-stat--offline::after { background: var(--acs-slate); }
    .acs-stat--crit::after { background: var(--acs-red); }

    .acs-stat__row {
        align-items: flex-start;
        display: flex;
        justify-content: space-between;
    }

    .acs-stat__ico {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        font-size: 20px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .acs-stat--total .acs-stat__ico { background: rgba(37, 99, 235, .12); color: var(--acs-blue); }
    .acs-stat--online .acs-stat__ico { background: rgba(16, 185, 129, .12); color: var(--acs-green); }
    .acs-stat--offline .acs-stat__ico { background: rgba(100, 116, 139, .12); color: var(--acs-slate); }
    .acs-stat--crit .acs-stat__ico { background: rgba(239, 68, 68, .12); color: var(--acs-red); }

    .acs-stat__val {
        color: var(--acs-ink);
        font-family: var(--acs-mono);
        font-size: 30px;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1;
        margin-top: 16px;
    }

    .acs-stat__lbl {
        color: var(--acs-slate);
        font-size: 12.5px;
        font-weight: 600;
        margin-top: 6px;
    }

    .acs-stat__pct {
        color: var(--acs-mut);
        font-size: 11px;
        font-weight: 600;
    }

    /* ---------- Toolbar ---------- */
    .acs-toolbar {
        align-items: center;
        background: #fff;
        border: 1px solid var(--acs-line);
        border-radius: 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
        padding: 14px 16px;
    }

    .acs-search {
        flex: 1 1 260px;
        position: relative;
    }

    .acs-search i {
        color: var(--acs-mut);
        left: 14px;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
    }

    .acs-search input {
        background: #f8fafc;
        border: 1px solid var(--acs-line);
        border-radius: 11px;
        color: var(--acs-ink);
        font-size: 14px;
        padding: 10px 14px 10px 40px;
        transition: border-color .15s ease, box-shadow .15s ease;
        width: 100%;
    }

    .acs-search input:focus {
        background: #fff;
        border-color: var(--acs-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        outline: none;
    }

    .acs-toolbar select {
        background: #f8fafc;
        border: 1px solid var(--acs-line);
        border-radius: 11px;
        color: var(--acs-ink);
        font-size: 13.5px;
        font-weight: 500;
        min-width: 150px;
        padding: 10px 12px;
    }

    .acs-toolbar select:focus {
        border-color: var(--acs-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        outline: none;
    }

    .acs-count {
        color: var(--acs-slate);
        font-size: 12.5px;
        font-weight: 600;
        margin-left: auto;
        white-space: nowrap;
    }

    .acs-count b { color: var(--acs-ink); font-family: var(--acs-mono); }

    /* ---------- Device grid ---------- */
    .acs-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    }

    .acs-dev {
        animation: acsRise .4s ease both;
        background: #fff;
        border: 1px solid var(--acs-line);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .acs-dev:hover {
        border-color: #d4dcea;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.09);
        transform: translateY(-3px);
    }

    @keyframes acsRise {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .acs-dev__top {
        align-items: center;
        border-bottom: 1px solid var(--acs-line);
        display: flex;
        gap: 12px;
        padding: 15px 16px;
    }

    .acs-dev__ont {
        align-items: center;
        background: #f1f5f9;
        border-radius: 11px;
        color: #475569;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 19px;
        height: 40px;
        justify-content: center;
        width: 40px;
    }

    .acs-dev__id { min-width: 0; }

    .acs-dev__user {
        color: var(--acs-ink);
        font-size: 14.5px;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .acs-dev__serial {
        color: var(--acs-mut);
        font-family: var(--acs-mono);
        font-size: 11.5px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .acs-pill {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 11.5px;
        font-weight: 700;
        gap: 6px;
        margin-left: auto;
        padding: 5px 11px;
    }

    .acs-pill--on { background: rgba(16, 185, 129, .12); color: #059669; }
    .acs-pill--off { background: rgba(100, 116, 139, .12); color: #64748b; }
    .acs-pill__dot { border-radius: 999px; height: 7px; width: 7px; }
    .acs-pill--on .acs-pill__dot { background: var(--acs-green); box-shadow: 0 0 0 3px rgba(16,185,129,.18); }
    .acs-pill--off .acs-pill__dot { background: #94a3b8; }

    /* RX power meter */
    .acs-rx {
        align-items: center;
        display: flex;
        gap: 14px;
        padding: 16px;
    }

    .acs-rx__val {
        color: var(--acs-ink);
        font-family: var(--acs-mono);
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1;
    }

    .acs-rx__unit { color: var(--acs-mut); font-size: 13px; font-weight: 600; }

    .acs-rx__tag {
        border-radius: 6px;
        display: inline-block;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: .04em;
        margin-top: 7px;
        padding: 2px 8px;
        text-transform: uppercase;
    }

    .acs-rx__tag--good { background: rgba(16, 185, 129, .13); color: #059669; }
    .acs-rx__tag--crit { background: rgba(239, 68, 68, .13); color: #dc2626; }
    .acs-rx__tag--na { background: #f1f5f9; color: #94a3b8; }

    .acs-bars {
        align-items: flex-end;
        display: flex;
        gap: 4px;
        height: 38px;
        margin-left: auto;
    }

    .acs-bars span {
        background: #e2e8f0;
        border-radius: 3px;
        width: 8px;
    }

    .acs-bars span:nth-child(1) { height: 30%; }
    .acs-bars span:nth-child(2) { height: 50%; }
    .acs-bars span:nth-child(3) { height: 72%; }
    .acs-bars span:nth-child(4) { height: 100%; }

    .acs-bars--good span.on { background: var(--acs-green); }
    .acs-bars--crit span.on { background: var(--acs-red); }

    /* spec grid */
    .acs-specs {
        border-top: 1px dashed var(--acs-line);
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .acs-spec {
        border-bottom: 1px dashed var(--acs-line);
        padding: 11px 16px;
    }

    .acs-spec:nth-child(odd) { border-right: 1px dashed var(--acs-line); }

    .acs-spec__k {
        color: var(--acs-mut);
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .acs-spec__v {
        color: var(--acs-ink);
        font-size: 13px;
        font-weight: 600;
        margin-top: 3px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .acs-spec__v.mono { font-family: var(--acs-mono); font-size: 12.5px; }

    .acs-dev__foot {
        display: flex;
        gap: 9px;
        margin-top: auto;
        padding: 14px 16px;
    }

    .acs-btn {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 10px;
        cursor: pointer;
        display: inline-flex;
        flex: 1;
        font-size: 13px;
        font-weight: 600;
        gap: 6px;
        justify-content: center;
        padding: 9px 12px;
        transition: background .15s ease, border-color .15s ease, color .15s ease;
    }

    .acs-btn--primary { background: var(--acs-blue); color: #fff; }
    .acs-btn--primary:hover { background: #1d4ed8; color: #fff; }
    .acs-btn--ghost { background: #fff; border-color: var(--acs-line); color: var(--acs-slate); }
    .acs-btn--ghost:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--acs-ink); }

    /* empty state */
    .acs-empty {
        background: #fff;
        border: 1px dashed #d4dcea;
        border-radius: 16px;
        color: var(--acs-slate);
        display: none;
        padding: 54px 20px;
        text-align: center;
    }

    .acs-empty i { color: #cbd5e1; display: block; font-size: 44px; margin-bottom: 10px; }
    .acs-empty.show { display: block; }
</style>
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid acs-page">

        @if (session('auth_errors'))
            <div class="alert alert-danger" role="alert">
                @foreach ((array) session('auth_errors') as $err)
                    {{ $err }}
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2" aria-hidden="true"></i>
                @foreach ((array) session('success') as $suc)
                    {{ $suc }}
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="btn-close"></button>
            </div>
        @endif

        @if (! ($dataLoaded ?? true))
            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="mdi mdi-information-outline me-2"></i> Data perangkat belum dimuat dari GenieACS. Klik tombol untuk memuat data.</span>
                <a href="{{ request()->fullUrlWithQuery(['show_data' => '1']) }}" class="btn btn-primary btn-sm">
                    <i class="mdi mdi-download"></i> Tampilkan Data
                </a>
            </div>
        @else

        {{-- Head --}}
        <div class="acs-head">
            <div class="acs-head__title">
                <span class="acs-head__mark"><i class="uil-wifi"></i></span>
                <div>
                    <h4>GenieACS Devices</h4>
                    <p class="acs-head__sub">Monitoring perangkat ONT / modem pelanggan secara real-time</p>
                </div>
            </div>
            <span class="acs-head__live"><span class="acs-pulse"></span> {{ $online }} device online</span>
        </div>

        {{-- Stats --}}
        @php
            $totalDev = count($devices);
            $onlinePct = $totalDev ? round($online / $totalDev * 100) : 0;
        @endphp
        <div class="acs-stats">
            <div class="acs-stat acs-stat--total">
                <div class="acs-stat__row">
                    <span class="acs-stat__ico"><i class="uil-server-network"></i></span>
                </div>
                <div class="acs-stat__val"><span data-plugin="counterup">{{ $totalDev }}</span></div>
                <div class="acs-stat__lbl">Total Device</div>
            </div>

            <div class="acs-stat acs-stat--online">
                <div class="acs-stat__row">
                    <span class="acs-stat__ico"><i class="uil-check-circle"></i></span>
                    <span class="acs-stat__pct">{{ $onlinePct }}%</span>
                </div>
                <div class="acs-stat__val">{{ $online }}</div>
                <div class="acs-stat__lbl">Online</div>
            </div>

            <div class="acs-stat acs-stat--offline">
                <div class="acs-stat__row">
                    <span class="acs-stat__ico"><i class="uil-times-circle"></i></span>
                </div>
                <div class="acs-stat__val">{{ $offline }}</div>
                <div class="acs-stat__lbl">Offline</div>
            </div>

            <div class="acs-stat acs-stat--crit">
                <div class="acs-stat__row">
                    <span class="acs-stat__ico"><i class="uil-exclamation-triangle"></i></span>
                </div>
                <div class="acs-stat__val"><span data-plugin="counterup">{{ $criticalRxPowerCount }}</span></div>
                <div class="acs-stat__lbl">Critical RX Power</div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="acs-toolbar">
            <div class="acs-search">
                <i class="uil-search"></i>
                <input type="text" id="acsSearch" placeholder="Cari username, serial, IP, atau model…" autocomplete="off">
            </div>
            <select id="deviceStatus">
                <option value="all">Semua Status</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
            </select>
            <select id="rxPower">
                <option value="all">Semua RX Power</option>
                <option value="rx-power-good">RX Good</option>
                <option value="rx-power-critical">RX Critical</option>
            </select>
            <div class="acs-count">Menampilkan <b id="acsShown">{{ $totalDev }}</b> / <b>{{ $totalDev }}</b></div>
        </div>

        {{-- Device grid --}}
        <div id="acsGrid" class="acs-grid">
            @foreach ($devices as $device)
                @php
                    $rxRaw = $device['rxPower'] ?? 'N/A';
                    $rxNum = is_numeric($rxRaw) ? (float) $rxRaw : null;
                    $rxClass = $device['rxPowerClass'] ?? '';
                    // signal fill across the "good" window (-27 .. -8 dBm) → 0..4 bars
                    $bars = 0;
                    if ($rxNum !== null) {
                        $pct = ($rxNum + 27) / 19;
                        $pct = max(0, min(1, $pct));
                        $bars = (int) round($pct * 4);
                        if ($rxClass === 'rx-power-critical' && $bars < 1) { $bars = 1; }
                    }
                    $meter = $rxClass === 'rx-power-good' ? 'good' : ($rxClass === 'rx-power-critical' ? 'crit' : 'na');
                    $search = strtolower(trim(
                        ($device['pppUsername'] ?? '').' '.
                        ($device['serialNumber'] ?? '').' '.
                        ($device['pppoeIP'] ?? '').' '.
                        ($device['productClass'] ?? '')
                    ));
                @endphp
                <div class="acs-dev"
                     data-status="{{ $device['online'] ? 'online' : 'offline' }}"
                     data-rx-power="{{ $rxClass }}"
                     data-search="{{ $search }}"
                     style="animation-delay: {{ min($loop->index * 35, 420) }}ms">

                    <div class="acs-dev__top">
                        <span class="acs-dev__ont"><i class="uil-router"></i></span>
                        <div class="acs-dev__id">
                            <div class="acs-dev__user">{{ $device['pppUsername'] ?: 'Unknown' }}</div>
                            <div class="acs-dev__serial" title="{{ $device['serialNumber'] ?? '' }}">{{ $device['serialNumber'] ?? 'N/A' }}</div>
                        </div>
                        @if ($device['online'])
                            <span class="acs-pill acs-pill--on"><span class="acs-pill__dot"></span> Online</span>
                        @else
                            <span class="acs-pill acs-pill--off"><span class="acs-pill__dot"></span> Offline</span>
                        @endif
                    </div>

                    <div class="acs-rx">
                        <div>
                            <span class="acs-rx__val">{{ $rxNum !== null ? number_format($rxNum, 1) : '—' }}</span>
                            <span class="acs-rx__unit">dBm</span>
                            @if ($meter === 'good')
                                <div class="acs-rx__tag acs-rx__tag--good">RX Good</div>
                            @elseif ($meter === 'crit')
                                <div class="acs-rx__tag acs-rx__tag--crit">RX Critical</div>
                            @else
                                <div class="acs-rx__tag acs-rx__tag--na">No Data</div>
                            @endif
                        </div>
                        <div class="acs-bars acs-bars--{{ $meter === 'na' ? 'good' : $meter }}">
                            @for ($b = 1; $b <= 4; $b++)
                                <span class="{{ $b <= $bars ? 'on' : '' }}"></span>
                            @endfor
                        </div>
                    </div>

                    <div class="acs-specs">
                        <div class="acs-spec">
                            <div class="acs-spec__k">IP Address</div>
                            <div class="acs-spec__v mono">{{ $device['pppoeIP'] ?? 'N/A' }}</div>
                        </div>
                        <div class="acs-spec">
                            <div class="acs-spec__k">Model</div>
                            <div class="acs-spec__v" title="{{ $device['productClass'] ?? '' }}">{{ $device['productClass'] ?? 'N/A' }}</div>
                        </div>
                        <div class="acs-spec">
                            <div class="acs-spec__k">Terhubung</div>
                            <div class="acs-spec__v">{{ $device['userConnected'] ?? '0' }} perangkat</div>
                        </div>
                        <div class="acs-spec">
                            <div class="acs-spec__k">Last Inform</div>
                            <div class="acs-spec__v">{{ $device['lastinform'] ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="acs-dev__foot">
                        <form action="{{ url('server/acs/device/edit') }}" method="post" class="d-flex gap-2 w-100">
                            @csrf
                            <input type="hidden" name="deviceId" value="{{ $device['id'] ?? '' }}">
                            <button type="submit" class="acs-btn acs-btn--primary"><i class="uil-wifi"></i> Info Wifi</button>
                            <button type="submit" formaction="{{ url('server/acs/device/refresh') }}" class="acs-btn acs-btn--ghost"><i class="uil-sync"></i> Refresh</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="acsEmpty" class="acs-empty">
            <i class="uil-search-alt"></i>
            <div>Tidak ada device yang cocok dengan filter.</div>
        </div>

        @endif

    </div>
</div>
@endsection

@section('scripts')
@if ($dataLoaded ?? true)
<script>
    (function () {
        var search = document.getElementById('acsSearch');
        var statusSel = document.getElementById('deviceStatus');
        var rxSel = document.getElementById('rxPower');
        var cards = Array.prototype.slice.call(document.querySelectorAll('.acs-dev'));
        var shown = document.getElementById('acsShown');
        var empty = document.getElementById('acsEmpty');

        function apply() {
            var q = (search.value || '').trim().toLowerCase();
            var st = statusSel.value;
            var rx = rxSel.value;
            var visible = 0;

            cards.forEach(function (card) {
                var okStatus = st === 'all' || card.getAttribute('data-status') === st;
                var okRx = rx === 'all' || card.getAttribute('data-rx-power') === rx;
                var okText = !q || card.getAttribute('data-search').indexOf(q) !== -1;
                var show = okStatus && okRx && okText;
                card.style.display = show ? 'flex' : 'none';
                if (show) visible++;
            });

            shown.textContent = visible;
            empty.classList.toggle('show', visible === 0);
        }

        search.addEventListener('input', apply);
        statusSel.addEventListener('change', apply);
        rxSel.addEventListener('change', apply);
    })();
</script>
@endif
@endsection
