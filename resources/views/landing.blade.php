<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<meta name="description" content="ANNORTY NET (PT Landak Annorty Net) — internet fiber mandiri ber-ASN AS153122 untuk Landak & sekitarnya. Stabil, latensi rendah, dukungan lokal 24/7.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700;1,9..144,600&family=Hanken+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#faf5ec; --panel:#fffdf9; --ink:#211a14; --muted:#6d6156; --faint:#9c9083;
  --line:#ece0cf; --line2:#f2e9db;
  --red:#dd3324; --red-d:#c22a1d; --gold:#eea31c; --gold-d:#cf8c0f;
  --red-soft:#fdeeeb; --gold-soft:#fdf4e0;
  --radius:20px; --shadow-sm:0 1px 2px rgba(33,26,20,.05);
  --shadow:0 3px 6px rgba(33,26,20,.05),0 20px 44px -22px rgba(33,26,20,.28);
  --disp:'Fraunces',serif; --body:'Hanken Grotesk',sans-serif; --mono:'JetBrains Mono',monospace;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--body);background:var(--bg);color:var(--ink);line-height:1.6;overflow-x:hidden;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
img{max-width:100%}

.bg-fx{position:fixed;inset:0;z-index:-2;pointer-events:none;
  background:
    radial-gradient(46% 38% at 84% -4%, rgba(238,163,28,.16), transparent 62%),
    radial-gradient(42% 40% at 4% 8%, rgba(221,51,36,.09), transparent 60%),
    var(--bg);}
.dots{position:fixed;inset:0;z-index:-2;pointer-events:none;opacity:.55;
  background-image:radial-gradient(rgba(33,26,20,.05) 1px,transparent 1px);background-size:24px 24px;
  mask-image:radial-gradient(circle at 50% 0,#000,transparent 60%);-webkit-mask-image:radial-gradient(circle at 50% 0,#000,transparent 60%);}

.wrap{max-width:1180px;margin:0 auto;padding:0 22px}
.eyebrow{font-family:var(--mono);font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:var(--red)}
.tag{display:inline-flex;align-items:center;gap:9px;font-family:var(--mono);font-size:12px;letter-spacing:.05em;
  color:var(--muted);border:1px solid var(--line);background:var(--panel);padding:7px 14px;border-radius:100px;box-shadow:var(--shadow-sm)}
.dot{width:7px;height:7px;border-radius:50%;background:var(--red);box-shadow:0 0 0 4px rgba(221,51,36,.14);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}

/* topbar */
.topbar{background:var(--ink);color:#f7ecd8;font-family:var(--mono);font-size:11.5px;letter-spacing:.08em;text-align:center;padding:8px 12px}
.topbar b{color:var(--gold)}

/* buttons */
.btn{display:inline-flex;align-items:center;gap:9px;font-family:var(--body);font-weight:600;font-size:15px;
  padding:13px 22px;border-radius:12px;border:1px solid transparent;cursor:pointer;transition:.22s ease;white-space:nowrap;line-height:1}
.btn svg{width:18px;height:18px}
.btn-primary{background:linear-gradient(180deg,#e63c2d,var(--red-d));color:#fff;box-shadow:0 8px 22px -8px rgba(221,51,36,.6)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 14px 30px -10px rgba(221,51,36,.7)}
.btn-gold{background:linear-gradient(180deg,#f4b13a,var(--gold-d));color:#3d2a02}
.btn-gold:hover{transform:translateY(-2px)}
.btn-ghost{background:var(--panel);border-color:var(--line);color:var(--ink);box-shadow:var(--shadow-sm)}
.btn-ghost:hover{border-color:var(--red);color:var(--red);transform:translateY(-2px)}

/* nav */
header{position:sticky;top:0;z-index:60;background:rgba(250,245,236,.82);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}
nav{display:flex;align-items:center;justify-content:space-between;height:78px;gap:16px}
.brand{display:flex;align-items:center;gap:10px}
.brand img{height:46px;width:auto;display:block}
.brand .txt{font-family:var(--disp);font-weight:700;font-size:21px;letter-spacing:-.01em}
.brand .txt b{color:var(--red)}.brand .txt i{color:var(--gold);font-style:normal}
.nav-links{display:flex;gap:30px;font-size:15px;font-weight:500;color:var(--muted)}
.nav-links a:hover{color:var(--ink)}
.nav-cta{display:flex;gap:11px;align-items:center}
.nav-cta .btn{padding:10px 18px;font-size:14px}
.burger{display:none;background:var(--panel);border:1px solid var(--line);border-radius:10px;width:44px;height:44px;align-items:center;justify-content:center;cursor:pointer;box-shadow:var(--shadow-sm)}
.burger svg{width:20px;height:20px}
.mobile-menu{display:none;flex-direction:column;padding:10px 22px 22px;border-bottom:1px solid var(--line);background:var(--bg)}
.mobile-menu a{padding:13px 4px;font-weight:600;color:var(--ink);border-bottom:1px solid var(--line2)}
.mobile-menu .btn{margin-top:14px;justify-content:center}
body.menu-open .mobile-menu{display:flex}

/* hero */
.hero{padding:clamp(44px,7vw,88px) 0 56px;display:grid;grid-template-columns:1.05fr .95fr;gap:52px;align-items:center}
.hero h1{font-family:var(--disp);font-weight:700;font-size:clamp(42px,6.6vw,74px);line-height:1.02;letter-spacing:-.02em;margin:22px 0 22px}
.hero h1 em{font-style:italic;color:var(--red)}
.hero h1 .u{background:linear-gradient(transparent 68%,rgba(238,163,28,.42) 0);padding:0 .04em}
.hero p.lead{font-size:clamp(16px,1.5vw,19px);color:var(--muted);max-width:540px;margin-bottom:30px}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:30px}
.hero-cta .textlink{font-weight:600;color:var(--ink);display:inline-flex;align-items:center;gap:6px}
.hero-cta .textlink:hover{color:var(--red)}
.chips{display:flex;gap:22px;flex-wrap:wrap;font-size:13.5px;color:var(--muted)}
.chips span{display:flex;align-items:center;gap:8px}
.chips svg{width:17px;height:17px;color:var(--red)}

/* hero emblem */
.emblem{position:relative;width:100%;max-width:440px;margin-left:auto;aspect-ratio:1/1;display:grid;place-items:center}
.emblem::before{content:"";position:absolute;width:82%;height:82%;border-radius:50%;
  background:radial-gradient(circle,var(--gold-soft) 0%,transparent 68%);z-index:-1}
.netsvg{position:relative;width:100%;height:100%;overflow:visible;filter:drop-shadow(0 14px 26px rgba(221,51,36,.1))}
.hstroke{fill:none;stroke:var(--red);stroke-width:3.2;stroke-linejoin:round;stroke-linecap:round}
.hfill{fill:var(--gold-soft)}
.fiberpath{fill:none;stroke:url(#fl);stroke-width:3.4;stroke-linecap:round;stroke-dasharray:6 12;animation:fflow 1.5s linear infinite}
@keyframes fflow{to{stroke-dashoffset:-32}}
.twave{fill:none;stroke:var(--gold);stroke-width:2.6;transform-origin:112px 88px;opacity:0;animation:twave 2.6s ease-out infinite}
@keyframes twave{0%{transform:scale(.25);opacity:.85}100%{transform:scale(1.75);opacity:0}}
.emblem .fb{position:absolute;font-family:var(--mono);font-size:11px;font-weight:600;background:var(--panel);border:1px solid var(--line);border-radius:100px;padding:7px 13px;box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:7px;z-index:2}
.emblem .fb.a{top:8%;right:2%;color:var(--red)}
.emblem .fb.b{bottom:9%;left:0%;color:var(--gold-d)}

/* reveal */
.reveal{opacity:0;transform:translateY(18px);animation:rise .85s cubic-bezier(.2,.7,.2,1) forwards}
@keyframes rise{to{opacity:1;transform:none}}
.d1{animation-delay:.05s}.d2{animation-delay:.13s}.d3{animation-delay:.22s}.d4{animation-delay:.31s}.d5{animation-delay:.4s}.d6{animation-delay:.5s}

/* marquee */
.marquee{border-block:1px solid var(--line);background:var(--panel);overflow:hidden;padding:15px 0}
.marquee .track{display:flex;gap:42px;width:max-content;animation:scroll 30s linear infinite;font-family:var(--mono);font-size:13px;color:var(--muted)}
.marquee span{display:flex;align-items:center;gap:11px;white-space:nowrap}
.marquee span::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--gold)}
@keyframes scroll{to{transform:translateX(-50%)}}

/* sections */
.sec{padding:clamp(58px,8vw,100px) 0}
.sec-head{max-width:660px;margin-bottom:48px}
.sec-head h2{font-family:var(--disp);font-weight:700;font-size:clamp(30px,4.4vw,46px);letter-spacing:-.02em;line-height:1.06;margin:14px 0 12px}
.sec-head h2 em{font-style:italic;color:var(--red)}
.sec-head p{color:var(--muted);font-size:17px}

/* features */
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.feat{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:28px;transition:.28s;box-shadow:var(--shadow-sm);position:relative;overflow:hidden}
.feat::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--red),var(--gold));transform:scaleX(0);transform-origin:left;transition:.3s}
.feat:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
.feat:hover::before{transform:scaleX(1)}
.feat .ic{width:48px;height:48px;border-radius:13px;display:grid;place-items:center;margin-bottom:18px;background:var(--red-soft);color:var(--red)}
.feat .ic svg{width:24px;height:24px}
.feat h3{font-family:var(--disp);font-weight:600;font-size:20px;margin-bottom:8px;letter-spacing:-.01em}
.feat p{color:var(--muted);font-size:14.5px}

/* paket */
.pak-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px}
.pak{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:28px 26px;display:flex;flex-direction:column;transition:.28s;box-shadow:var(--shadow-sm)}
.pak:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
.pak.hot{border-color:var(--red);box-shadow:0 22px 48px -22px rgba(221,51,36,.5);position:relative}
.pak .badge{align-self:flex-start;font-family:var(--mono);font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#fff;background:linear-gradient(90deg,var(--red),#e8632c);padding:4px 11px;border-radius:100px;margin-bottom:14px}
.pak .spd{font-family:var(--disp);font-weight:700;font-size:33px;letter-spacing:-.02em;line-height:1.1}
.pak .nm{color:var(--muted);font-size:14px;margin:4px 0 20px}
.pak ul{list-style:none;margin:0 0 22px;display:flex;flex-direction:column;gap:10px}
.pak li{display:flex;gap:9px;font-size:14px;color:var(--muted);align-items:center}
.pak li svg{width:16px;height:16px;color:var(--gold-d);flex:none}
.pak .price{font-family:var(--disp);font-weight:700;font-size:26px;margin-top:auto;letter-spacing:-.01em}
.pak .price span{font-size:13px;font-weight:500;color:var(--faint);font-family:var(--body)}

/* band */
.band{background:var(--ink);color:#f7ecd8;border-radius:26px;padding:clamp(38px,5vw,64px);overflow:hidden;position:relative}
.band::after{content:"";position:absolute;inset:0;background:radial-gradient(55% 120% at 88% 8%,rgba(238,163,28,.28),transparent 55%),radial-gradient(45% 100% at 6% 100%,rgba(221,51,36,.25),transparent 55%);pointer-events:none}
.band-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:28px;position:relative}
.band .num{font-family:var(--disp);font-weight:700;font-size:clamp(30px,4vw,46px);letter-spacing:-.02em;color:#fff}
.band .num.g{color:var(--gold)}
.band p{color:rgba(247,236,216,.6);font-size:14px;margin-top:6px}

/* cta */
.cta{text-align:center;background:linear-gradient(180deg,var(--gold-soft),var(--panel));border:1px solid var(--line);border-radius:26px;padding:clamp(40px,6vw,72px);box-shadow:var(--shadow)}
.cta h2{font-family:var(--disp);font-weight:700;font-size:clamp(30px,4.6vw,48px);letter-spacing:-.02em;margin:12px 0 14px;line-height:1.04}
.cta h2 em{font-style:italic;color:var(--red)}
.cta p{color:var(--muted);max-width:520px;margin:0 auto 30px;font-size:17px}
.cta .hero-cta{justify-content:center;margin-bottom:0}

/* footer */
footer{border-top:1px solid var(--line);padding:56px 0 30px;margin-top:20px}
.foot-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr;gap:34px;margin-bottom:36px}
.foot-grid img{height:56px;margin-bottom:14px}
.foot-grid>div:first-child p{color:var(--muted);font-size:14px;max-width:330px}
.foot-col h4{font-family:var(--disp);font-size:14px;letter-spacing:.02em;color:var(--ink);margin-bottom:16px}
.foot-col a,.foot-col div{display:block;color:var(--muted);font-size:14px;margin-bottom:11px;transition:.2s}
.foot-col a:hover{color:var(--red)}
.asn-badge{display:inline-flex;align-items:center;gap:8px;font-family:var(--mono);font-size:12px;color:var(--red);border:1px solid var(--line);border-radius:9px;padding:7px 13px;margin-top:14px;background:var(--red-soft)}
.foot-motto{font-family:var(--disp);font-style:italic;font-size:13.5px;color:var(--gold-d);margin-top:14px}
.foot-bottom{border-top:1px solid var(--line);padding-top:22px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;color:var(--faint);font-size:13px}

@media(max-width:900px){
  .hero{grid-template-columns:1fr;gap:34px}
  .emblem{order:-1;max-width:340px;margin:0 auto}
  .feat-grid{grid-template-columns:1fr 1fr}
  .band-grid{grid-template-columns:1fr 1fr;gap:24px}
  .foot-grid{grid-template-columns:1fr 1fr}
  .nav-links{display:none}
  .nav-cta .btn-ghost{display:none}
  .burger{display:flex}
  .brand img{height:40px}
}
@media(max-width:560px){
  .wrap{padding:0 16px}
  .feat-grid{grid-template-columns:1fr}
  .foot-grid{grid-template-columns:1fr}
  .chips{gap:14px}
  .hero-cta .btn{flex:1 1 auto;justify-content:center}
  .topbar{font-size:10px}
}
</style>
</head>
<body>
<div class="bg-fx"></div><div class="dots"></div>

@php
  $loginUrl = url('auth/login');
  $tagihanUrl = url('tagihan');
  $logoSrc = $logo ? asset('assets/logo/'.$logo) : null;
@endphp

<div class="topbar">ADIL KA'TALINO · BA'CURAMIN KA'SARUGA · BA'SENGAT KA'JUBATA &nbsp;—&nbsp; <b>PT LANDAK ANNORTY NET · AS153122</b></div>

<header>
  <div class="wrap"><nav>
    <a href="/" class="brand">
      @if($logoSrc)<img src="{{ $logoSrc }}" alt="ANNORTY NET">@else<span class="txt"><b>ANNORTY</b> <i>NET</i></span>@endif
    </a>
    <div class="nav-links">
      <a href="#layanan">Layanan</a><a href="#paket">Paket</a><a href="#kenapa">Kenapa Kami</a><a href="#kontak">Kontak</a>
    </div>
    <div class="nav-cta">
      <a href="{{ $tagihanUrl }}" class="btn btn-ghost">Cek Tagihan</a>
      <a href="{{ $loginUrl }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>Login
      </a>
      <button class="burger" onclick="document.body.classList.toggle('menu-open')" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
    </div>
  </nav></div>
  <div class="mobile-menu">
    <a href="#layanan" onclick="document.body.classList.remove('menu-open')">Layanan</a>
    <a href="#paket" onclick="document.body.classList.remove('menu-open')">Paket</a>
    <a href="#kenapa" onclick="document.body.classList.remove('menu-open')">Kenapa Kami</a>
    <a href="#kontak" onclick="document.body.classList.remove('menu-open')">Kontak</a>
    <a href="{{ $tagihanUrl }}" class="btn btn-ghost">Cek Tagihan</a>
    <a href="{{ $loginUrl }}" class="btn btn-primary">Login Pelanggan</a>
  </div>
</header>

<!-- HERO -->
<section class="wrap hero">
  <div>
    <span class="tag reveal d1"><span class="dot"></span> Internet Fiber · Kabupaten Landak</span>
    <h1 class="reveal d2">Terhubung lewat <span class="u">jaringan fiber</span> <em>milik kami sendiri.</em></h1>
    <p class="lead reveal d3">ANNORTY NET mengoperasikan jaringan fiber mandiri ber-<b style="color:var(--ink)">ASN AS153122</b> untuk Landak &amp; sekitarnya — koneksi stabil, latensi rendah, didukung penuh oleh tim lokal.</p>
    <div class="hero-cta reveal d4">
      <a href="{{ $loginUrl }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>Login Pelanggan
      </a>
      <a href="{{ $tagihanUrl }}" class="btn btn-ghost">Cek Tagihan</a>
      <a href="#paket" class="textlink">Lihat Paket <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    </div>
    <div class="chips reveal d5">
      <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg> Fiber to the Home</span>
      <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Uptime 99.8%</span>
      <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6M21 19a2 2 0 0 1-2 2h-1v-4h3M3 19a2 2 0 0 0 2 2h1v-4H3"/></svg> Dukungan Lokal 24/7</span>
    </div>
  </div>
  <div class="emblem reveal d3">
    <svg class="netsvg" viewBox="0 0 400 400">
      <defs>
        <linearGradient id="fl" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#eea31c"/><stop offset="1" stop-color="#dd3324"/></linearGradient>
        <radialGradient id="core"><stop offset="0" stop-color="#f4b13a"/><stop offset="1" stop-color="#dd3324"/></radialGradient>
      </defs>

      {{-- tanah --}}
      <line x1="44" y1="332" x2="362" y2="332" stroke="var(--line)" stroke-width="2.5" stroke-dasharray="2 9" stroke-linecap="round"/>

      {{-- MENARA BTS + sinyal --}}
      <circle class="twave" cx="112" cy="88" r="13"/>
      <circle class="twave" cx="112" cy="88" r="13" style="animation-delay:.85s"/>
      <circle class="twave" cx="112" cy="88" r="13" style="animation-delay:1.7s"/>
      <path class="hstroke" d="M96 330 L112 100"/>
      <path class="hstroke" d="M128 330 L112 100"/>
      <path class="hstroke" style="stroke-width:2.2" d="M96 330 L128 268 M128 330 L96 268 M100 268 L124 214 M124 268 L100 214 M104 214 L120 166 M120 214 L104 166"/>
      <path class="hstroke" style="stroke-width:2.3" d="M101 268 H123 M105 214 H119 M108 166 H116"/>
      <path class="hstroke" d="M112 100 L112 84"/>
      <circle cx="112" cy="82" r="5.5" fill="url(#core)"/>
      <path class="hstroke" style="stroke-width:2.4" d="M90 333 L96 330 M134 333 L128 330"/>

      {{-- KABEL FIBER OPTIK (menara -> rumah) --}}
      <path class="fiberpath" d="M112 330 C 150 360, 214 306, 262 316"/>

      {{-- RUMAH pelanggan --}}
      <circle class="twave" cx="300" cy="220" r="8" style="animation-delay:.4s;transform-origin:300px 220px;stroke:var(--red)"/>
      <path class="hfill" d="M250 268 L300 226 L350 268 Z"/>
      <path class="hstroke" d="M244 270 L300 224 L356 270"/>
      <path class="hfill" d="M260 268 H340 V330 H260 Z"/>
      <path class="hstroke" d="M260 268 V330 H340 V268"/>
      <path class="hstroke" style="stroke-width:2.6" d="M292 330 V303 Q292 298 297 298 H303 Q308 298 308 303 V330"/>
      <rect x="268" y="285" width="16" height="15" rx="2" fill="var(--gold)" opacity=".9"/>
      <path class="hstroke" style="stroke-width:2.4" d="M300 224 L300 212"/>
      <circle cx="300" cy="210" r="3.6" fill="var(--red)"/>
    </svg>
    <span class="fb a"><span class="dot"></span> Online</span>
    <span class="fb b">AS153122 · Fiber</span>
  </div>
</section>

<div class="marquee"><div class="track">
  @for($i=0;$i<2;$i++)
    <span>AS153122 Autonomous System</span><span>Fiber to the Home</span><span>Latensi Rendah</span>
    <span>Peering Lokal</span><span>Bandwidth Simetris</span><span>Monitoring 24 Jam</span><span>Tim Teknis Lokal</span>
  @endfor
</div></div>

<!-- LAYANAN -->
<section class="wrap sec" id="layanan">
  <div class="sec-head">
    <span class="eyebrow">Layanan</span>
    <h2>Dibangun untuk koneksi yang <em>bisa diandalkan.</em></h2>
    <p>Dari kabel fiber sampai ke perangkat Anda, hingga jaringan inti ber-ASN sendiri — semuanya kami kelola langsung.</p>
  </div>
  @php
    $features = [
      ['M13 2 3 14h9l-1 8 10-12h-9l1-8z','Fiber to the Home','Kabel fiber optik langsung ke rumah Anda — bukan sekadar Wi-Fi tetangga.'],
      ['M4 4h16v6H4zM4 14h16v6H4zM8 7h.01M8 17h.01','Jaringan Mandiri (ASN)','Kami punya nomor AS sendiri (AS153122) dan rute internet independen.'],
      ['M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20','Cepat & Simetris','Bandwidth upload–download seimbang, mantap untuk kerja, kelas online, & gaming.'],
      ['M3 18v-6a9 9 0 0 1 18 0v6M21 19a2 2 0 0 1-2 2h-1v-4h3M3 19a2 2 0 0 0 2 2h1v-4H3','Dukungan Lokal 24/7','Tim teknis di Landak siap bantu. Lapor gangguan cukup lewat WhatsApp.'],
      ['M22 12h-4l-3 9L9 3l-3 9H2','Monitoring Real-time','Jaringan dipantau NMS 24 jam untuk deteksi dini & penanganan cepat.'],
      ['M20 6 9 17l-5-5','Bayar Mudah','Cek dan bayar tagihan online kapan saja lewat portal pelanggan.'],
    ];
  @endphp
  <div class="feat-grid">
    @foreach($features as $i => $f)
      <div class="feat reveal d{{ min(6,$i+1) }}">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $f[0] }}"/></svg></div>
        <h3>{{ $f[1] }}</h3><p>{{ $f[2] }}</p>
      </div>
    @endforeach
  </div>
</section>

<!-- PAKET -->
<section class="wrap sec" id="paket" style="padding-top:0">
  <div class="cta">
    <span class="eyebrow">Paket Internet</span>
    <h2>Tanya paket &amp; harga <em>langsung ke admin.</em></h2>
    <p>Ketersediaan jaringan &amp; pilihan paket berbeda tiap lokasi. Hubungi admin ANNORTY NET untuk rekomendasi paket, harga, dan jadwal pemasangan fiber di area Anda.</p>
    <div class="hero-cta">
      @if(!empty($waAdmin))
        <a href="https://wa.me/{{ $waAdmin }}?text=Halo%20admin%20ANNORTY%20NET%2C%20saya%20ingin%20tanya%20paket%20internet." target="_blank" rel="noopener" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.1-1.3A10 10 0 1 0 12 2zm0 2a8 8 0 1 1-4.2 14.8l-.3-.2-2.6.7.7-2.5-.2-.3A8 8 0 0 1 12 4zm4.3 9.4c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-3.2-2.8c-.1-.2 0-.4.1-.5l.4-.5.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3A2.9 2.9 0 0 0 6 9.6c0 1.7 1.3 3.4 1.4 3.6.2.2 2.5 3.8 6 5.1 2 .7 2.4.6 2.9.5.7-.1 1.4-.6 1.6-1.1.2-.6.2-1 .1-1.1z"/></svg>
          Hubungi Admin
        </a>
        <a href="{{ $tagihanUrl }}" class="btn btn-ghost">Cek Tagihan</a>
      @else
        <a href="{{ $tagihanUrl }}" class="btn btn-primary">Cek Tagihan</a>
        <a href="{{ $loginUrl }}" class="btn btn-ghost">Login Pelanggan</a>
      @endif
    </div>
  </div>
</section>

<!-- KENAPA / STATS -->
<section class="wrap sec" id="kenapa" style="padding-top:0">
  <div class="band"><div class="band-grid">
    <div><div class="num g">AS153122</div><p>Nomor Autonomous System milik sendiri</p></div>
    <div><div class="num">99.8%</div><p>Uptime jaringan inti</p></div>
    <div><div class="num">24/7</div><p>Pemantauan &amp; dukungan</p></div>
    <div><div class="num">100%</div><p>Fiber optik ke pelanggan</p></div>
  </div></div>
</section>

<!-- CTA -->
<section class="wrap sec" id="kontak" style="padding-top:0">
  <div class="cta">
    <span class="eyebrow">Siap terhubung?</span>
    <h2>Bergabung dengan <em>ANNORTY NET.</em></h2>
    <p>Sudah jadi pelanggan? Masuk ke portal untuk cek tagihan &amp; layanan. Ingin berlangganan? Hubungi kami untuk pemasangan di area Anda.</p>
    <div class="hero-cta">
      <a href="{{ $loginUrl }}" class="btn btn-primary">Login Pelanggan</a>
      <a href="{{ $tagihanUrl }}" class="btn btn-ghost">Cek Tagihan</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer><div class="wrap">
  <div class="foot-grid">
    <div>
      @if($logoSrc)<img src="{{ $logoSrc }}" alt="ANNORTY NET">@else<div class="brand"><span class="txt"><b>ANNORTY</b> <i>NET</i></span></div>@endif
      <p>PT Landak Annorty Net — penyedia layanan internet fiber mandiri untuk Kabupaten Landak &amp; sekitarnya, Kalimantan Barat.</p>
      <div class="asn-badge"><span class="dot"></span> ASN AS153122</div>
      <div class="foot-motto">“Adil Ka’Talino, Ba’Curamin Ka’Saruga, Ba’Sengat Ka’Jubata”</div>
    </div>
    <div class="foot-col">
      <h4>Menu</h4>
      <a href="#layanan">Layanan</a><a href="#paket">Paket Internet</a><a href="#kenapa">Kenapa Kami</a>
      <a href="{{ $tagihanUrl }}">Cek Tagihan</a><a href="{{ $loginUrl }}">Login Pelanggan</a>
    </div>
    <div class="foot-col">
      <h4>Kontak</h4>
      <div>Landak, Kalimantan Barat</div><div>WhatsApp: hubungi admin</div><div>Layanan pelanggan 24/7</div>
    </div>
  </div>
  <div class="foot-bottom">
    <span>© {{ date('Y') }} PT Landak Annorty Net. Semua hak dilindungi.</span>
    <span style="font-family:var(--mono)">ANNORTY NET · AS153122</span>
  </div>
</div></footer>
</body>
</html>
