@extends('admin.layout')

@section('content')
<style>
    :root {
        --wa-accent: #00a884;
        --wa-accent-dark: #008069;
        --wa-accent-soft: #d9fdd3;
        --wa-header: #f0f2f5;
        --wa-toolbar: #f0f2f5;
        --wa-canvas: #efeae2;
        --wa-surface: #ffffff;
        --wa-hover: #f5f6f6;
        --wa-selected: #f0f2f5;
        --wa-border: #e9edef;
        --wa-text: #111b21;
        --wa-muted: #667781;
        --wa-danger-bg: #ffe1e1;
        --wa-danger: #b42318;
        --wa-bubble-in: #ffffff;
        --wa-bubble-out: #d9fdd3;
    }
    .wa-inbox {
        display: flex;
        height: clamp(560px, calc(100dvh - 150px), 860px);
        min-height: 540px;
        background: var(--wa-surface);
        border: 1px solid var(--wa-border);
        border-radius: .5rem;
        overflow: hidden;
        box-shadow: 0 1px 1px rgba(11,20,26,.07), 0 2px 5px rgba(11,20,26,.12);
    }

    /* ---- Sidebar ---- */
    .wa-sidebar {
        width: 360px;
        min-width: 300px;
        display: flex;
        flex-direction: column;
        background: var(--wa-surface);
        border-right: 1px solid rgba(0,0,0,.05);
    }
    .wa-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: 0 1rem;
        min-height: 60px;
        background: var(--wa-header);
        color: var(--wa-text);
        border-bottom: 1px solid var(--wa-border);
    }
    .wa-sidebar-title { font-weight: 600; font-size: 1rem; color: var(--wa-text); display: flex; align-items: center; gap: .4rem; }
    .wa-conn { font-size: .72rem; font-weight: 500; display: inline-flex; align-items: center; color: var(--wa-muted); }
    .wa-conn.is-online { color: var(--wa-accent); }
    .wa-conn.is-offline { color: #e44b4b; }
    .conn-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:5px; }
    .conn-dot.online { background:#53bdeb; animation: connBlink 1.4s infinite; }
    .conn-dot.offline { background:#ff6b6b; }
    @keyframes connBlink { 0%{box-shadow:0 0 0 0 rgba(83,189,235,0.6);} 70%{box-shadow:0 0 0 6px rgba(83,189,235,0);} 100%{box-shadow:0 0 0 0 rgba(83,189,235,0);} }
    @media (prefers-reduced-motion: reduce) { .conn-dot.online { animation: none; } }

    .wa-search-bar {
        padding: .5rem .75rem;
        background: var(--wa-toolbar);
        border-bottom: 1px solid var(--wa-border);
    }
    .wa-search-input {
        width: 100%;
        border: none;
        border-radius: 8px;
        padding: .4rem .75rem .4rem 2rem;
        font-size: .85rem;
        background: #fff;
        color: var(--wa-text);
    }
    .wa-search-wrap { position: relative; }
    .wa-search-wrap i { position: absolute; left: .6rem; top: 50%; transform: translateY(-50%); color: var(--wa-muted); font-size: .9rem; pointer-events: none; }
    .wa-search-input:focus { outline: none; box-shadow: 0 0 0 2px rgba(0,168,132,.2); }

    .wa-conversation-list { flex: 1; overflow-y: auto; }
    .wa-conversation {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .7rem 1rem;
        text-decoration: none;
        border-bottom: 1px solid var(--wa-border);
        cursor: pointer;
        transition: background .12s ease;
    }
    .wa-conversation:hover { background: var(--wa-hover); }
    .wa-conversation.is-active { background: var(--wa-selected); }
    .wa-avatar {
        flex: 0 0 auto;
        width: 49px; height: 49px;
        border-radius: 50%;
        background: linear-gradient(135deg, #00a884, #008069);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: 1.1rem;
        text-transform: uppercase;
        flex-shrink: 0;
    }
    .wa-conversation-main { min-width: 0; flex: 1; }
    .wa-conversation-top { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; }
    .wa-conversation-title { font-weight: 400; color: var(--wa-text); font-size: .92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wa-conversation-time { font-size: .72rem; color: var(--wa-muted); flex: 0 0 auto; }
    .wa-conversation-preview { font-size: .82rem; color: var(--wa-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; }
    .wa-empty-list { text-align: center; color: var(--wa-muted); padding: 2rem 1rem; }

    /* ---- Chat pane ---- */
    .wa-chat { flex: 1; display: flex; flex-direction: column; min-width: 0; background: var(--wa-canvas); }
    .wa-chat-header {
        display: flex; align-items: center; gap: .85rem;
        padding: 0 1rem;
        min-height: 60px;
        background: var(--wa-header);
        color: var(--wa-text);
        border-bottom: 1px solid var(--wa-border);
    }
    .wa-back { display: none; color: var(--wa-text); font-size: 1.3rem; text-decoration: none; width: 36px; height: 36px; border-radius: 50%; align-items: center; justify-content: center; flex-shrink: 0; }
    .wa-chat-header .wa-avatar { width: 40px; height: 40px; font-size: .95rem; }
    .wa-chat-meta { min-width: 0; flex: 1; }
    .wa-chat-name { font-weight: 600; color: var(--wa-text); font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wa-chat-sub { font-size: .74rem; color: var(--wa-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wa-window { text-align: right; }
    .wa-window-time { display: block; color: var(--wa-muted); font-size: .66rem; margin-bottom: .15rem; white-space: nowrap; }
    .wa-pill { font-size: .68rem; font-weight: 600; padding: .2rem .55rem; border-radius: 999px; white-space: nowrap; }
    .wa-pill.is-open { background: #d9fdd3; color: #008069; }
    .wa-pill.is-closed { background: #ffe1e1; color: #b42318; }

    /* ---- Thread / messages ---- */
    .wa-thread {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 5%;
        background-color: var(--wa-canvas);
        background-image:
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cg fill='%23000' fill-opacity='0.018'%3E%3Cpath d='M14 16c0-2 1-3 3-3s3 1 3 3-1 3-3 3-3-1-3-3zm40 30c0-2 1-3 3-3s3 1 3 3-1 3-3 3-3-1-3-3zM50 10c0-1 .5-2 2-2s2 1 2 2-.5 2-2 2-2-1-2-2zM20 50c0-1 .5-2 2-2s2 1 2 2-.5 2-2 2-2-1-2-2zM65 25c0-1.5 1-2.5 2.5-2.5S70 23.5 70 25s-1 2.5-2.5 2.5S65 26.5 65 25zM10 35c0-1.5 1-2.5 2.5-2.5S15 33.5 15 35s-1 2.5-2.5 2.5S10 36.5 10 35zM55 60c0-1 .5-2 2-2s2 1 2 2-.5 2-2 2-2-1-2-2zM30 70c0-1 .5-2 2-2s2 1 2 2-.5 2-2 2-2-1-2-2z'/%3E%3C/g%3E%3C/svg%3E");
        display: flex;
        flex-direction: column;
        gap: .3rem;
    }
    .wa-message-row { display: flex; }
    .wa-message-row.is-outgoing { justify-content: flex-end; }
    .wa-message-row.is-incoming { justify-content: flex-start; }
    .wa-bubble {
        position: relative;
        max-width: min(65%, 620px);
        padding: .4rem .7rem .45rem;
        border-radius: 10px;
        box-shadow: 0 1px .5px rgba(11,20,26,.08);
        font-size: .875rem;
        color: var(--wa-text);
        min-width: 80px;
    }
    .wa-bubble.is-incoming {
        background: var(--wa-bubble-in);
        border-top-left-radius: 0;
    }
    .wa-bubble.is-incoming::before {
        content: '';
        position: absolute;
        top: 0; left: -8px;
        width: 8px; height: 13px;
        background: var(--wa-bubble-in);
        clip-path: polygon(100% 0, 100% 100%, 0 0);
    }
    .wa-bubble.is-outgoing {
        background: var(--wa-bubble-out);
        border-top-right-radius: 0;
    }
    .wa-bubble.is-outgoing::before {
        content: '';
        position: absolute;
        top: 0; right: -8px;
        width: 8px; height: 13px;
        background: var(--wa-bubble-out);
        clip-path: polygon(0 0, 0 100%, 100% 0);
    }
    .wa-bubble.is-failed { background: var(--wa-danger-bg); border-top-right-radius: 0; }
    .wa-bubble.is-failed::before { background: var(--wa-danger-bg); }
    .wa-bubble.is-failed .wa-message-meta { color: var(--wa-danger); }
    .wa-template-label {
        display: inline-block;
        font-size: .68rem;
        background: rgba(0,0,0,.06);
        color: var(--wa-muted);
        padding: .1rem .4rem;
        border-radius: 4px;
        margin-bottom: .25rem;
    }
    .wa-media img { display: block; border-radius: 6px; margin-bottom: .35rem; max-width: 260px; max-height: 260px; object-fit: contain; }
    .message-body { white-space: pre-wrap; word-break: break-word; line-height: 1.35; }
    .wa-message-meta {
        float: right;
        text-align: right;
        font-size: .65rem;
        color: var(--wa-muted);
        margin: .15rem 0 0 .5rem;
        user-select: none;
    }
    .wa-reply-action {
        background: none; border: none; padding: 0; margin-top: .1rem;
        color: var(--wa-accent-dark); font-size: .72rem; font-weight: 600; cursor: pointer;
        opacity: 0; transition: opacity .12s ease;
    }
    .wa-bubble:hover .wa-reply-action,
    .wa-reply-action:focus-visible { opacity: 1; }
    @media (hover: none) { .wa-reply-action { opacity: 1; } }

    .wa-thread-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: var(--wa-muted); }

    /* ---- Composer ---- */
    .wa-composer { background: var(--wa-toolbar); border-top: 1px solid var(--wa-border); padding: .55rem 1rem; }
    .wa-signature-options { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: .5rem; }
    .wa-reply-preview {
        background: var(--wa-surface);
        border-left: 4px solid var(--wa-accent);
        border-radius: 6px;
        padding: .4rem .6rem;
        margin-bottom: .5rem;
        display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem;
    }
    .wa-reply-preview .wa-quote-label { color: var(--wa-accent-dark); font-size: .72rem; font-weight: 600; }
    .wa-reply-preview .wa-quote-text { color: var(--wa-muted); font-size: .78rem; max-width: 600px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wa-compose-row { display: flex; align-items: flex-end; gap: .5rem; }
    .wa-compose-input {
        flex: 1;
        border: none;
        border-radius: 8px;
        padding: .55rem 1rem;
        resize: none;
        font-size: .875rem;
        background: #fff;
    }
    .wa-compose-input:focus { outline: none; box-shadow: 0 0 0 2px rgba(0,168,132,.2); }
    .wa-send-button {
        flex: 0 0 auto;
        width: 44px; height: 44px;
        border-radius: 50%;
        border: none;
        background: var(--wa-accent);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        transition: background .15s ease;
    }
    .wa-send-button:hover { background: var(--wa-accent-dark); }
    .wa-attachment-form { margin-top: .55rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .wa-attachment-form .form-control { max-width: 260px; }
    .wa-attachment-hint { font-size: .7rem; color: var(--wa-muted); flex-basis: 100%; margin: 0; }

    @media (max-width: 991.98px) {
        .wa-inbox { height: calc(100dvh - 130px); border: none; border-radius: 0; }
        .wa-sidebar { width: 100% !important; min-width: 0; display: flex !important; }
        .wa-chat { display: none !important; }
        .wa-inbox.has-selection .wa-sidebar { display: none !important; }
        .wa-inbox.has-selection .wa-chat { display: flex !important; }
        .wa-back { display: flex !important; align-items: center; justify-content: center; width: 40px; height: 40px; font-size: 1.5rem; }
        .wa-bubble { max-width: 86%; }
        .wa-thread { padding: 1rem 3%; }
    }
    @media (max-width: 575.98px) {
        .wa-inbox { border-radius: 0; height: calc(100dvh - 120px); }
        .wa-attachment-form .form-control { max-width: 100%; }
        .wa-thread { padding: .75rem 4%; }
        .wa-sidebar-header { min-height: 54px; }
        .wa-chat-header { min-height: 54px; }
        .wa-avatar { width: 42px; height: 42px; font-size: 1rem; }
        .wa-chat-header .wa-avatar { width: 36px; height: 36px; }
        .wa-conversation { padding: .55rem .75rem; gap: .65rem; }
        .wa-conversation-title { font-size: .88rem; }
        .wa-conversation-preview { font-size: .78rem; }
        .wa-signature-options { gap: .5rem; }
        .wa-compose-row { gap: .4rem; }
        .wa-compose-input { font-size: .85rem; padding: .5rem .75rem; }
        .wa-send-button { width: 40px; height: 40px; font-size: 1.1rem; }
    }
</style>
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
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

                @php
                    $activeGateway = \App\Support\WhatsAppGatewayResolver::active();
                    $metaActive = $activeGateway && \App\Support\WhatsAppGatewayResolver::isMeta($activeGateway);
                    $webhookRow = \Illuminate\Support\Facades\DB::table('webhook')->first();
                    $webhookOn = $webhookRow && ($webhookRow->status ?? 'off') === 'on';
                    $connOnline = $metaActive && $webhookOn;
                @endphp

                @if (! $metaActive || ! $webhookOn)
                    <div class="alert alert-warning">
                        <strong>Inbox belum aktif.</strong> Syarat:
                        <ol class="mb-0">
                            <li>Gateway WhatsApp Meta harus aktif (mode ON). <a href="{{ url('admin/whatsapp') }}">Cek Gateway</a></li>
                            <li>Webhook harus ON. <a href="{{ url('admin/webhook') }}">Cek Webhook</a></li>
                            <li>Webhook URL Meta: <code>{{ url('webhook/whatsapp/meta') }}</code> (HTTPS publik)</li>
                        </ol>
                    </div>
                @endif

                <div class="wa-inbox {{ $selectedNumber !== '' ? 'has-selection' : '' }}">
                    <div class="wa-sidebar">
                        <div class="wa-sidebar-header">
                            <span class="wa-sidebar-title"><i class="uil uil-whatsapp"></i> WhatsApp Inbox</span>
                            <span id="metaConnStatus" class="wa-conn {{ $connOnline ? 'is-online' : 'is-offline' }}" role="status" aria-live="polite">
                                <span class="conn-dot {{ $connOnline ? 'online' : 'offline' }}" id="metaConnDot" aria-hidden="true"></span>
                                <span id="metaConnText">{{ $connOnline ? 'Terhubung' : 'Terputus' }}</span>
                            </span>
                        </div>
                        <div class="wa-search-bar">
                            <div class="wa-search-wrap">
                                <i class="uil uil-search"></i>
                                <input type="text" id="waSearchInput" class="wa-search-input" placeholder="Cari atau mulai percakapan baru" aria-label="Cari percakapan">
                            </div>
                        </div>
                        <div class="wa-conversation-list" id="conversationList">
                            @forelse ($conversations as $conv)
                                @php $convTitle = $conv->customer_name ?: ($conv->from_name ?: $conv->from_number); @endphp
                                <a href="{{ url('admin/whatsapp/inbox?number='.$conv->from_number) }}"
                                   class="wa-conversation {{ $selectedNumber === $conv->from_number ? 'is-active' : '' }}"
                                   data-conversation-number="{{ $conv->from_number }}"
                                   data-search-title="{{ strtolower($convTitle) }}"
                                   data-search-number="{{ strtolower($conv->from_number) }}"
                                   @if ($selectedNumber === $conv->from_number) aria-current="page" @endif>
                                    <span class="wa-avatar" aria-hidden="true">{{ mb_substr($convTitle, 0, 1) }}</span>
                                    <span class="wa-conversation-main">
                                        <span class="wa-conversation-top">
                                            <span class="wa-conversation-title">{{ $convTitle }}</span>
                                            <span class="wa-conversation-time">{{ optional($conv->created_at)->format('H:i') }}</span>
                                        </span>
                                        <p class="wa-conversation-preview">{{ $conv->direction === 'in' ? '' : 'Anda: ' }}{{ $conv->body }}</p>
                                    </span>
                                </a>
                            @empty
                                <div class="wa-empty-list">
                                    <i class="uil uil-comments h2"></i>
                                    <p class="mb-0">Belum ada percakapan masuk.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="wa-chat">
                        @if ($selectedNumber !== '')
                            @php $headerTitle = $customer?->nama ?: ($messages->first()?->from_name ?: $selectedNumber); @endphp
                            <div class="wa-chat-header">
                                <a href="{{ url('admin/whatsapp/inbox') }}" class="wa-back" aria-label="Kembali ke daftar percakapan"><i class="uil uil-angle-left-b"></i></a>
                                <span class="wa-avatar" aria-hidden="true">{{ mb_substr($headerTitle, 0, 1) }}</span>
                                <div class="wa-chat-meta">
                                    <div class="wa-chat-name">{{ $headerTitle }}</div>
                                    <div class="wa-chat-sub">
                                        {{ $selectedNumber }}
                                        @if ($customer)
                                            &middot; {{ $customer->level }} &middot; {{ $customer->status_account }}
                                        @else
                                            &middot; <span style="color:#e65100;">Bukan user terdaftar</span>
                                        @endif
                                    </div>
                                </div>
                                <div id="windowStatus" class="wa-window" role="status" aria-live="polite">
                                    @if ($lastIncomingAt)
                                        <span class="wa-window-time">Pesan masuk terakhir: {{ $lastIncomingAt->format('d M H:i') }}</span>
                                        @if ($canReplyText)
                                            <span class="wa-pill is-open">Jendela balas aktif</span>
                                        @else
                                            <span class="wa-pill is-closed">Jendela 24 jam habis</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="wa-thread" id="chatThread" role="log" aria-live="polite" aria-relevant="additions"
                                 data-number="{{ $selectedNumber }}" data-last-id="{{ optional($messages->last())->id ?? 0 }}">
                                @foreach ($messages as $msg)
                                    @php $out = $msg->direction === 'out'; $failed = $out && $msg->status === 'failed'; @endphp
                                    <div class="wa-message-row {{ $out ? 'is-outgoing' : 'is-incoming' }}" data-message-id="{{ $msg->id }}">
                                        <div class="wa-bubble {{ $out ? ($failed ? 'is-failed' : 'is-outgoing') : 'is-incoming' }}">
                                            @if ($out && str_starts_with((string) $msg->message_type, 'template'))
                                                <span class="wa-template-label">Notifikasi: {{ str_replace('template:', '', $msg->message_type) }}</span>
                                            @endif
                                            @if ($msg->hasMedia())
                                                <a class="wa-media" href="{{ url('admin/whatsapp/inbox/media/'.$msg->id) }}" target="_blank" rel="noopener">
                                                    <img src="{{ url('admin/whatsapp/inbox/media/'.$msg->id) }}" alt="Gambar chat">
                                                </a>
                                            @endif
                                            <span class="message-body">{{ $msg->body }}</span>
                                            <span class="wa-message-meta">
                                                {{ optional($msg->created_at)->format('H:i') }}
                                                @if ($out)
                                                    &middot; {{ $failed ? '✗' : '✓✓' }}
                                                @endif
                                            </span>
                                            @if ($canReplyText && $msg->direction === 'in' && filled($msg->meta_message_id))
                                                <button type="button" class="wa-reply-action reply-message-btn" data-message-id="{{ $msg->id }}">
                                                    <i class="uil uil-reply"></i> Balas
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="wa-composer" id="replyBox">
                                @if ($canReplyText)
                                    <form method="post" action="{{ url('admin/whatsapp/inbox/send') }}" id="replyForm">
                                        @csrf
                                        <input type="hidden" name="number" value="{{ $selectedNumber }}">
                                        <input type="hidden" name="reply_to_message_id" id="replyToMessageId">
                                        <div id="replyPreview" class="wa-reply-preview d-none">
                                            <div>
                                                <div class="wa-quote-label"><i class="uil uil-reply"></i> Membalas pesan pelanggan</div>
                                                <div id="replyPreviewText" class="wa-quote-text"></div>
                                            </div>
                                            <button type="button" id="cancelReplyBtn" class="btn-close btn-sm" aria-label="Batal membalas"></button>
                                        </div>
                                        <div class="wa-signature-options">
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="radio" name="signature_mode" id="sigAuto" value="auto" checked onchange="document.getElementById('signatureNameInput').disabled = true;">
                                                <label class="form-check-label small" for="sigAuto">Signature: <strong>{{ auth()->user()->nama ?? 'Admin' }}</strong></label>
                                            </div>
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="radio" name="signature_mode" id="sigManual" value="manual" onchange="document.getElementById('signatureNameInput').disabled = false; document.getElementById('signatureNameInput').focus();">
                                                <label class="form-check-label small" for="sigManual">Manual</label>
                                            </div>
                                            <input type="text" name="signature_name" id="signatureNameInput" class="form-control form-control-sm" style="max-width: 200px;" placeholder="Nama signature..." aria-label="Nama signature manual" disabled>
                                        </div>
                                        <div class="wa-compose-row">
                                            <textarea name="message" class="wa-compose-input" rows="1" placeholder="Ketik pesan" aria-label="Tulis balasan" required></textarea>
                                            <button type="submit" class="wa-send-button" aria-label="Kirim balasan"><i class="uil uil-message"></i></button>
                                        </div>
                                    </form>
                                    <form method="post" action="{{ url('admin/whatsapp/inbox/send-image') }}" enctype="multipart/form-data" class="wa-attachment-form">
                                        @csrf
                                        <input type="hidden" name="number" value="{{ $selectedNumber }}">
                                        <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png" aria-label="Pilih gambar" required>
                                        <input type="text" name="caption" class="form-control form-control-sm" maxlength="1024" placeholder="Caption (opsional)" aria-label="Caption gambar">
                                        <button type="submit" class="btn btn-sm btn-outline-success"><i class="uil uil-image"></i> Kirim Gambar</button>
                                        <p class="wa-attachment-hint">JPEG atau PNG, maksimal 5 MB. Hanya tersedia selama jendela balas 24 jam aktif.</p>
                                    </form>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="uil uil-clock"></i>
                                        Jendela balas 24 jam sudah habis. Anda hanya bisa mengirim <strong>template message</strong> ke nomor ini.
                                        <a href="{{ url('admin/whatsapp/message/text-message') }}" class="alert-link">Kirim via Test Message</a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="wa-thread-empty">
                                <div class="text-center">
                                    <i class="uil uil-comment-alt-message display-4"></i>
                                    <p class="mt-2">Pilih percakapan untuk mulai membalas.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var POLL_INTERVAL = 5000; // 5 detik
    var pollUrl = '{{ url('admin/whatsapp/inbox/poll') }}';
    var inboxBaseUrl = '{{ url('admin/whatsapp/inbox') }}';
    var thread = document.getElementById('chatThread');
    var canReplyText = @json($canReplyText);
    var replyToMessageId = document.getElementById('replyToMessageId');
    var replyPreview = document.getElementById('replyPreview');
    var replyPreviewText = document.getElementById('replyPreviewText');
    var replyTextarea = document.querySelector('#replyForm textarea[name="message"]');
    if (thread) { thread.scrollTop = thread.scrollHeight; }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderMessage(msg) {
        var out = msg.direction === 'out';
        var failed = out && msg.status === 'failed';
        var bubble = out ? (failed ? 'is-failed' : 'is-outgoing') : 'is-incoming';
        var isTemplate = out && (msg.message_type || '').indexOf('template') === 0;
        var badge = isTemplate
            ? '<span class="wa-template-label">Notifikasi: ' + escapeHtml((msg.message_type || '').replace('template:', '')) + '</span>'
            : '';
        var statusMark = out ? ' &middot; ' + (failed ? '✗' : '✓✓') : '';
        var media = msg.media_url
            ? '<a class="wa-media" href="' + escapeHtml(msg.media_url) + '" target="_blank" rel="noopener"><img src="' + escapeHtml(msg.media_url) + '" alt="Gambar chat"></a>'
            : '';
        var reply = canReplyText && msg.can_reply
            ? '<button type="button" class="wa-reply-action reply-message-btn" data-message-id="' + msg.id + '"><i class="uil uil-reply"></i> Balas</button>'
            : '';
        return '<div class="wa-message-row ' + (out ? 'is-outgoing' : 'is-incoming') + '" data-message-id="' + msg.id + '">' +
            '<div class="wa-bubble ' + bubble + '">' + badge + media +
            '<span class="message-body">' + escapeHtml(msg.body) + '</span>' +
            '<span class="wa-message-meta">' + escapeHtml(msg.created_at) + statusMark + '</span>' + reply +
            '</div></div>';
    }

    function clearReplySelection() {
        if (replyToMessageId) replyToMessageId.value = '';
        if (replyPreview) replyPreview.classList.add('d-none');
        if (replyPreviewText) replyPreviewText.textContent = '';
    }

    if (thread) {
        thread.addEventListener('click', function (event) {
            var button = event.target.closest('.reply-message-btn');
            if (!button || !replyToMessageId || !replyPreview || !replyPreviewText) return;
            var message = button.closest('[data-message-id]');
            var body = message ? message.querySelector('.message-body') : null;
            replyToMessageId.value = button.getAttribute('data-message-id');
            replyPreviewText.textContent = body ? body.textContent.trim() : '[Pesan]';
            replyPreview.classList.remove('d-none');
            if (replyTextarea) replyTextarea.focus();
        });
    }

    var cancelReplyBtn = document.getElementById('cancelReplyBtn');
    if (cancelReplyBtn) cancelReplyBtn.addEventListener('click', clearReplySelection);

    function scrollIfNeeded() {
        if (!thread) return;
        var nearBottom = (thread.scrollHeight - thread.scrollTop - thread.clientHeight) < 80;
        if (nearBottom) { thread.scrollTop = thread.scrollHeight; }
    }

    function refreshConversations(convos) {
        var list = document.getElementById('conversationList');
        if (!list) return;
        if (!convos.length) { return; }
        list.innerHTML = convos.map(function (c) {
            var initial = escapeHtml((c.title || ' ').charAt(0).toUpperCase());
            return '<a href="' + inboxBaseUrl + '?number=' + encodeURIComponent(c.from_number) + '" ' +
                'class="wa-conversation ' + (c.is_selected ? 'is-active' : '') + '" ' +
                'data-conversation-number="' + escapeHtml(c.from_number) + '" ' +
                'data-search-title="' + escapeHtml((c.title || '').toLowerCase()) + '" ' +
                'data-search-number="' + escapeHtml((c.from_number || '').toLowerCase()) + '"' +
                (c.is_selected ? ' aria-current="page"' : '') + '>' +
                '<span class="wa-avatar" aria-hidden="true">' + initial + '</span>' +
                '<span class="wa-conversation-main">' +
                '<span class="wa-conversation-top">' +
                '<span class="wa-conversation-title">' + escapeHtml(c.title) + '</span>' +
                '<span class="wa-conversation-time">' + escapeHtml(c.time) + '</span>' +
                '</span>' +
                '<p class="wa-conversation-preview">' + escapeHtml(c.preview) + '</p>' +
                '</span></a>';
        }).join('');
        filterConversations();
    }

    function pollInbox() {
        if (!thread) return;
        var number = thread.getAttribute('data-number');
        var lastId = parseInt(thread.getAttribute('data-last-id') || '0', 10);
        if (!number) return;

        fetch(pollUrl + '?number=' + encodeURIComponent(number) + '&after_id=' + lastId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
        .then(function (data) {
            setConnStatus(true);
            if (!data || !data.messages) return;
            if (data.messages.length) {
                data.messages.forEach(function (msg) {
                    thread.insertAdjacentHTML('beforeend', renderMessage(msg));
                    if (msg.id > lastId) { thread.setAttribute('data-last-id', msg.id); }
                });
                scrollIfNeeded();
            }
            refreshConversations(data.conversations || []);
            updateWindowStatus(data.can_reply_text);
        })
        .catch(function () { setConnStatus(false); });
    }

    function setConnStatus(online) {
        var dot = document.getElementById('metaConnDot');
        var text = document.getElementById('metaConnText');
        var wrap = document.getElementById('metaConnStatus');
        if (!dot || !text || !wrap) return;
        if (online) {
            dot.classList.add('online'); dot.classList.remove('offline');
            wrap.classList.add('is-online'); wrap.classList.remove('is-offline');
            text.textContent = 'Terhubung';
        } else {
            dot.classList.add('offline'); dot.classList.remove('online');
            wrap.classList.add('is-offline'); wrap.classList.remove('is-online');
            text.textContent = 'Terputus';
        }
    }

    function updateWindowStatus(canReply) {
        canReplyText = canReply;
        var replyBox = document.getElementById('replyBox');
        if (!replyBox) return;
        var hasForm = document.getElementById('replyForm');
        if (canReply && !hasForm) {
            location.reload(); // state berubah, reload untuk menampilkan form balas
        } else if (!canReply && hasForm) {
            location.reload();
        }
    }

    function filterConversations() {
        var input = document.getElementById('waSearchInput');
        if (!input) return;
        var query = input.value.trim().toLowerCase();
        var items = document.querySelectorAll('#conversationList .wa-conversation');
        items.forEach(function (item) {
            var title = item.getAttribute('data-search-title') || '';
            var number = item.getAttribute('data-search-number') || '';
            var match = query === '' || title.indexOf(query) !== -1 || number.indexOf(query) !== -1;
            item.style.display = match ? '' : 'none';
        });
    }

    var searchInput = document.getElementById('waSearchInput');
    if (searchInput) searchInput.addEventListener('input', filterConversations);

    if (replyTextarea) {
        replyTextarea.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }

    setInterval(pollInbox, POLL_INTERVAL);
</script>
@endsection
