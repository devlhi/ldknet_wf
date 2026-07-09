<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('assets/logo/'.$logo) }}">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <style>
        :root {
            --pri: #4f46e5;
            --pri-dark: #4338ca;
            --acc: #0ea5e9;
            --ink: #1e293b;
            --ink-dim: #64748b;
            --line: #e2e8f0;
            --field: #f8fafc;
        }

        * { box-sizing: border-box; }

        body.an-auth {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: inherit;
            color: var(--ink);
            position: relative;
            overflow: hidden;
            background: #eef2f9;
        }

        /* ==== background: nuansa kampung + indikator jaringan ==== */
        .an-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background:
                radial-gradient(circle at 14% 15%, rgba(250, 204, 21, .22) 0 8%, transparent 22%),
                linear-gradient(180deg, #f8fbff 0%, #eaf3ff 48%, #dcebf4 100%);
        }
        .an-bg::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(14, 165, 233, .055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14, 165, 233, .055) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,.65), rgba(0,0,0,.1));
            -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,.65), rgba(0,0,0,.1));
        }
        .an-sun {
            position: absolute;
            width: 130px;
            height: 130px;
            left: 10%;
            top: 11%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(253, 224, 71, .9) 0%, rgba(251, 191, 36, .35) 48%, rgba(251, 191, 36, 0) 72%);
            filter: blur(.2px);
        }
        .an-cloud {
            position: absolute;
            width: 160px;
            height: 54px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            box-shadow: 44px -14px 0 4px rgba(255, 255, 255, .66), 86px 2px 0 -2px rgba(255, 255, 255, .72);
            filter: blur(.2px);
            opacity: .82;
        }
        .an-cloud.one { left: 18%; top: 20%; transform: scale(.9); }
        .an-cloud.two { right: 12%; top: 15%; transform: scale(.72); opacity: .7; }
        .an-hill {
            position: absolute;
            left: -8%;
            right: -8%;
            bottom: 120px;
            height: 42vh;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }
        .an-hill.back {
            bottom: 145px;
            background: linear-gradient(180deg, rgba(125, 211, 252, .36), rgba(34, 197, 94, .18));
            transform: rotate(-2deg);
        }
        .an-hill.front {
            bottom: 70px;
            background: linear-gradient(180deg, rgba(187, 247, 208, .84), rgba(74, 222, 128, .28));
            transform: rotate(2deg);
        }
        .an-ground {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 185px;
            background:
                linear-gradient(180deg, rgba(220, 252, 231, .74), rgba(187, 247, 208, .92)),
                repeating-linear-gradient(90deg, rgba(22, 163, 74, .08) 0 2px, transparent 2px 34px);
        }
        .an-road {
            position: absolute;
            left: 50%;
            bottom: -26px;
            width: 360px;
            height: 230px;
            transform: translateX(-50%) perspective(360px) rotateX(62deg);
            transform-origin: bottom;
            clip-path: polygon(42% 0, 58% 0, 100% 100%, 0 100%);
            background: linear-gradient(180deg, rgba(148, 163, 184, .35), rgba(100, 116, 139, .2));
            opacity: .58;
        }
        .an-road::after {
            content: "";
            position: absolute;
            left: 49%;
            top: 8%;
            width: 8px;
            height: 86%;
            border-radius: 999px;
            background: repeating-linear-gradient(180deg, rgba(255, 255, 255, .78) 0 18px, transparent 18px 36px);
        }
        .an-house {
            position: absolute;
            width: 96px;
            height: 62px;
            bottom: 118px;
            border-radius: 8px 8px 5px 5px;
            background: rgba(255, 255, 255, .76);
            border: 1px solid rgba(148, 163, 184, .28);
            box-shadow: 0 16px 40px -24px rgba(15, 23, 42, .35);
        }
        .an-house::before {
            content: "";
            position: absolute;
            left: -9px;
            top: -34px;
            width: 114px;
            height: 48px;
            clip-path: polygon(50% 0, 100% 70%, 94% 100%, 6% 100%, 0 70%);
            background: linear-gradient(135deg, #f97316, #ef4444);
        }
        .an-house::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 29px;
            left: 39px;
            bottom: 0;
            border-radius: 5px 5px 0 0;
            background: rgba(79, 70, 229, .72);
            box-shadow: -27px -20px 0 -5px rgba(14, 165, 233, .42), 27px -20px 0 -5px rgba(14, 165, 233, .42);
        }
        .an-house.left { left: 12%; }
        .an-house.right { right: 10%; bottom: 105px; transform: scale(.82); opacity: .86; }
        .an-tower {
            position: absolute;
            width: 70px;
            height: 165px;
            bottom: 125px;
            right: 23%;
            opacity: .72;
        }
        .an-tower::before {
            content: "";
            position: absolute;
            left: 32px;
            top: 35px;
            width: 6px;
            height: 118px;
            border-radius: 999px;
            background: linear-gradient(180deg, #64748b, #94a3b8);
            box-shadow: -24px 118px 0 -2px rgba(100, 116, 139, .7), 24px 118px 0 -2px rgba(100, 116, 139, .7);
        }
        .an-tower::after {
            content: "";
            position: absolute;
            left: 19px;
            top: 70px;
            width: 34px;
            height: 70px;
            background: linear-gradient(135deg, transparent 46%, rgba(100, 116, 139, .72) 47% 53%, transparent 54%),
                        linear-gradient(45deg, transparent 46%, rgba(100, 116, 139, .72) 47% 53%, transparent 54%);
        }
        .an-wifi {
            position: absolute;
            width: 88px;
            height: 88px;
            right: -9px;
            top: -4px;
        }
        .an-wifi i {
            position: absolute;
            inset: 0;
            border: 3px solid rgba(14, 165, 233, .62);
            border-left-color: transparent;
            border-bottom-color: transparent;
            border-radius: 50%;
            transform: rotate(-45deg) scale(var(--s));
            animation: anSignal 2.2s ease-out infinite;
            animation-delay: var(--d);
        }
        .an-wifi i:nth-child(1) { --s: .38; --d: 0s; }
        .an-wifi i:nth-child(2) { --s: .62; --d: .18s; }
        .an-wifi i:nth-child(3) { --s: .88; --d: .36s; }
        .an-signal-card {
            position: absolute;
            left: 11%;
            bottom: 225px;
            display: inline-flex;
            align-items: end;
            gap: 4px;
            padding: .55rem .7rem;
            border-radius: 14px;
            background: rgba(255, 255, 255, .72);
            border: 1px solid rgba(255, 255, 255, .86);
            box-shadow: 0 18px 50px -30px rgba(15, 23, 42, .3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .an-signal-card span {
            width: 6px;
            border-radius: 999px;
            background: linear-gradient(180deg, #22c55e, #0ea5e9);
            animation: anBars 1.8s ease-in-out infinite;
        }
        .an-signal-card span:nth-child(1) { height: 12px; animation-delay: -.3s; }
        .an-signal-card span:nth-child(2) { height: 18px; animation-delay: -.15s; }
        .an-signal-card span:nth-child(3) { height: 26px; }
        .an-signal-card span:nth-child(4) { height: 34px; animation-delay: .15s; }
        .an-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(75px);
            opacity: .34;
            animation: anFloat 15s ease-in-out infinite;
        }
        .an-orb.a { width: 360px; height: 360px; top: -100px; left: -90px; background: #a5b4fc; }
        .an-orb.b { width: 320px; height: 320px; bottom: -120px; right: -80px; background: #7dd3fc; animation-delay: -6s; }
        .an-orb.c { width: 260px; height: 260px; top: 40%; right: 12%; background: #c7d2fe; opacity: .24; animation-delay: -3s; }
        @keyframes anFloat {
            0%, 100% { transform: translate(0, 0); }
            50%      { transform: translate(26px, -28px); }
        }
        @keyframes anSignal {
            0%   { opacity: .25; transform: rotate(-45deg) scale(calc(var(--s) * .82)); }
            45%  { opacity: .9; }
            100% { opacity: .22; transform: rotate(-45deg) scale(var(--s)); }
        }
        @keyframes anBars {
            0%, 100% { opacity: .55; transform: scaleY(.72); }
            50%      { opacity: 1; transform: scaleY(1); }
        }

        .an-wrap {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        /* ==== card ==== */
        .an-card {
            background: rgba(255, 255, 255, .88);
            border: 1px solid rgba(255, 255, 255, .9);
            border-radius: 20px;
            padding: 2.5rem 2.25rem 2.25rem;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, .9) inset,
                0 24px 60px -22px rgba(30, 41, 59, .28);
        }

        /* ==== brand header ==== */
        .an-brand {
            text-align: center;
            margin-bottom: 1.6rem;
        }
        .an-logo {
            height: 60px;
            width: auto;
            max-width: 210px;
            object-fit: contain;
            margin-bottom: .9rem;
            filter: drop-shadow(0 6px 14px rgba(79, 70, 229, .18));
        }
        .an-logo-fallback {
            width: 62px;
            height: 62px;
            margin: 0 auto .9rem;
            border-radius: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: .5px;
            background: linear-gradient(135deg, var(--pri) 0%, var(--acc) 100%);
            box-shadow: 0 12px 26px -8px rgba(79, 70, 229, .5);
        }
        .an-brand h1 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
            letter-spacing: -.01em;
        }
        .an-status {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-top: .75rem;
            padding: .28rem .75rem;
            border-radius: 999px;
            background: rgba(16, 185, 129, .1);
            border: 1px solid rgba(16, 185, 129, .22);
            color: #059669;
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .an-status .an-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, .6);
            animation: anPulse 1.8s ease-in-out infinite;
        }
        @keyframes anPulse {
            0%   { box-shadow: 0 0 0 0 rgba(16, 185, 129, .5); }
            70%  { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* ==== form ==== */
        .an-card h4 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
        }
        .an-card .text-muted { color: var(--ink-dim) !important; }
        .an-card .form-label {
            font-size: .8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: .4rem;
        }
        .an-field {
            position: relative;
        }
        .an-field > .an-field-icon {
            position: absolute;
            left: .95rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ink-dim);
            font-size: 1.05rem;
            pointer-events: none;
        }
        .an-card .form-control {
            border-radius: 11px;
            padding: .78rem .95rem;
            border: 1px solid var(--line);
            background: var(--field);
            font-size: .92rem;
            color: #0f172a;
        }
        .an-field .form-control { padding-left: 2.6rem; }
        .an-card .form-control::placeholder { color: #94a3b8; }
        .an-card .form-control:focus {
            border-color: var(--pri);
            background: #fff;
            color: #0f172a;
            box-shadow: 0 0 0 .2rem rgba(79, 70, 229, .16);
        }

        .an-field .an-toggle {
            position: absolute;
            right: .55rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: var(--ink-dim);
            padding: .3rem .45rem;
            line-height: 1;
            cursor: pointer;
        }
        .an-field .an-toggle:hover { color: var(--pri); }
        .an-field:has(.an-toggle) .form-control { padding-right: 2.7rem; }

        .an-forgot {
            font-size: .8rem;
            color: var(--pri);
            text-decoration: none;
            font-weight: 500;
        }
        .an-forgot:hover { text-decoration: underline; }

        /* ==== buttons (nama class dipertahankan utk forgot/reset) ==== */
        .an-btn-gradient {
            background: linear-gradient(135deg, var(--pri) 0%, var(--pri-dark) 100%);
            border: 0;
            color: #fff;
            border-radius: 11px;
            padding: .78rem 1rem;
            font-weight: 600;
            font-size: .95rem;
            box-shadow: 0 12px 26px -12px rgba(79, 70, 229, .7);
            transition: filter .15s ease, box-shadow .15s ease, transform .1s ease;
        }
        .an-btn-gradient:hover, .an-btn-gradient:focus {
            color: #fff;
            filter: brightness(1.06);
            box-shadow: 0 16px 32px -12px rgba(79, 70, 229, .8);
        }
        .an-btn-gradient:active { transform: translateY(1px); }

        .an-btn-outline {
            border: 1px solid var(--line);
            color: #475569;
            border-radius: 11px;
            padding: .74rem 1rem;
            font-weight: 600;
            font-size: .92rem;
            background: #fff;
            transition: all .15s ease;
        }
        .an-btn-outline:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .an-card .alert { border: 0; }

        .an-footer {
            text-align: center;
            color: var(--ink-dim);
            font-size: .76rem;
            margin-top: 1.4rem;
        }

        @media (max-width: 480px) {
            .an-card { padding: 2rem 1.5rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .an-status .an-dot { animation: none; }
            .an-orb { animation: none; }
        }
    </style>
</head>

<body class="an-auth">

    <div class="an-bg" aria-hidden="true">
        <span class="an-orb a"></span>
        <span class="an-orb b"></span>
        <span class="an-orb c"></span>
        <span class="an-sun"></span>
        <span class="an-cloud one"></span>
        <span class="an-cloud two"></span>
        <span class="an-hill back"></span>
        <span class="an-hill front"></span>
        <span class="an-ground"></span>
        <span class="an-road"></span>
        <span class="an-house left"></span>
        <span class="an-house right"></span>
        <span class="an-tower">
            <span class="an-wifi"><i></i><i></i><i></i></span>
        </span>
        <span class="an-signal-card"><span></span><span></span><span></span><span></span></span>
    </div>

    <div class="an-wrap">
        <div class="an-card">
            <div class="an-brand">
                <img src="{{ !empty($logo) ? asset('assets/logo/'.$logo) : asset('assets/images/logo-fdn.png') }}" alt="{{ $titletext }}" class="an-logo"
                     onerror="this.onerror=null;this.src='{{ asset('assets/images/logo-fdn.png') }}';this.onerror=function(){this.style.display='none';document.getElementById('an-logo-fb').style.display='flex';};">
                <div id="an-logo-fb" class="an-logo-fallback" style="display:none;">
                    {{ strtoupper(mb_substr($titletext ?? 'L', 0, 1)) }}
                </div>
                <h1>{{ $titletext }}</h1>
                <span class="an-status"><span class="an-dot"></span> Network Online</span>
            </div>

            @yield('content')
        </div>

        <div class="an-footer">
            Versi 3.0 &middot; © <script>document.write(new Date().getFullYear())</script> {{ $titletext }}
        </div>
    </div>

    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
</body>

</html>
