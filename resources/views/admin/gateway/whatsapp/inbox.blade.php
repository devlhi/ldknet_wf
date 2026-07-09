@extends('admin.layout')

@section('content')
<style>
    .conn-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; vertical-align:middle; }
    .conn-dot.online { background:#34c38f; animation: connBlink 1.2s infinite; }
    .conn-dot.offline { background:#f46a6a; animation:none; box-shadow:none; }
    @keyframes connBlink { 0%{box-shadow:0 0 0 0 rgba(52,195,143,0.7);} 70%{box-shadow:0 0 0 8px rgba(52,195,143,0);} 100%{box-shadow:0 0 0 0 rgba(52,195,143,0);} }
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

                <div class="card">
                    @php
                        $activeGateway = \App\Support\WhatsAppGatewayResolver::active();
                        $metaActive = $activeGateway && \App\Support\WhatsAppGatewayResolver::isMeta($activeGateway);
                        $webhookRow = \Illuminate\Support\Facades\DB::table('webhook')->first();
                        $webhookOn = $webhookRow && ($webhookRow->status ?? 'off') === 'on';
                        $connOnline = $metaActive && $webhookOn;
                    @endphp
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="uil uil-whatsapp me-1"></i> WhatsApp Inbox</h4>
                        <span id="metaConnStatus" class="small fw-semibold {{ $connOnline ? 'text-success' : 'text-danger' }}">
                            <span class="conn-dot {{ $connOnline ? 'online' : 'offline' }}" id="metaConnDot"></span>
                            <span id="metaConnText">{{ $connOnline ? 'Terhubung ke Meta Webhook' : 'Belum terhubung' }}</span>
                        </span>
                    </div>
                    @if (! $metaActive || ! $webhookOn)
                        <div class="alert alert-warning m-3">
                            <strong>Inbox belum aktif.</strong> Syarat:
                            <ol class="mb-0">
                                <li>Gateway WhatsApp Meta harus aktif (mode ON). <a href="{{ url('admin/whatsapp') }}">Cek Gateway</a></li>
                                <li>Webhook harus ON. <a href="{{ url('admin/webhook') }}">Cek Webhook</a></li>
                                <li>Webhook URL Meta: <code>{{ url('webhook/whatsapp/meta') }}</code> (HTTPS publik)</li>
                            </ol>
                        </div>
                    @endif
                    <div class="card-body p-0">
                        <div class="row g-0" style="min-height: 540px;">
                            <div class="col-lg-4 col-xl-3 border-end">
                                <div class="p-3 border-bottom bg-light">
                                    <small class="text-muted text-uppercase fw-semibold">Percakapan</small>
                                </div>
                                <div class="list-group list-group-flush" style="max-height: 540px; overflow-y: auto;" id="conversationList">
                                    @forelse ($conversations as $conv)
                                        <a href="{{ url('admin/whatsapp/inbox?number='.$conv->from_number) }}"
                                           class="list-group-item list-group-item-action {{ $selectedNumber === $conv->from_number ? 'active' : '' }}" data-conversation-number="{{ $conv->from_number }}">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="me-auto">
                                                    <h6 class="mb-0">{{ $conv->customer_name ?: ($conv->from_name ?: $conv->from_number) }}</h6>
                                                    <p class="mb-0 small text-truncate" style="max-width: 180px;">
                                                        {{ $conv->direction === 'in' ? '' : 'Anda: ' }}{{ $conv->body }}
                                                    </p>
                                                </div>
                                                <small class="{{ $selectedNumber === $conv->from_number ? 'text-white-50' : 'text-muted' }}">
                                                    {{ optional($conv->created_at)->format('H:i') }}
                                                </small>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="text-center text-muted p-4">
                                            <i class="uil uil-comments h2"></i>
                                            <p class="mb-0">Belum ada percakapan masuk.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="col-lg-8 col-xl-9">
                                @if ($selectedNumber !== '')
                                    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">{{ $customer?->nama ?: ($messages->first()?->from_name ?: $selectedNumber) }}</h6>
                                            <small class="text-muted">
                                                {{ $selectedNumber }}
                                                @if ($customer)
                                                    &middot; Level: {{ $customer->level }}
                                                    &middot; Status: {{ $customer->status_account }}
                                                @else
                                                    &middot; <span class="text-warning">Bukan user terdaftar</span>
                                                @endif
                                            </small>
                                        </div>
                                        <div class="text-end" id="windowStatus">
                                            @if ($lastIncomingAt)
                                                <small class="text-muted">Pesan masuk terakhir: {{ $lastIncomingAt->format('d M Y H:i') }}</small><br>
                                                @if ($canReplyText)
                                                    <span class="badge bg-success">Jendela balas aktif</span>
                                                @else
                                                    <span class="badge bg-danger">Jendela 24 jam habis</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div class="p-3" style="max-height: 400px; overflow-y: auto;" id="chatThread" data-number="{{ $selectedNumber }}" data-last-id="{{ optional($messages->last())->id ?? 0 }}">
                                        @foreach ($messages as $msg)
                                            <div class="d-flex mb-2 {{ $msg->direction === 'out' ? 'justify-content-end' : 'justify-content-start' }}" data-message-id="{{ $msg->id }}">
                                                <div class="p-2 rounded {{ $msg->direction === 'out' ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                                                    <div style="white-space: pre-wrap; word-break: break-word;">{{ $msg->body }}</div>
                                                    <small class="{{ $msg->direction === 'out' ? 'text-white-50' : 'text-muted' }}">
                                                        {{ optional($msg->created_at)->format('d M H:i') }}
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="p-3 border-top" id="replyBox">
                                        @if ($canReplyText)
                                            <form method="post" action="{{ url('admin/whatsapp/inbox/send') }}" id="replyForm">
                                                @csrf
                                                <input type="hidden" name="number" value="{{ $selectedNumber }}">
                                                <div class="mb-2 d-flex align-items-center gap-3">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="signature_mode" id="sigAuto" value="auto" checked onchange="document.getElementById('signatureNameInput').disabled = true;">
                                                        <label class="form-check-label small" for="sigAuto">Signature: <strong>{{ auth()->user()->nama ?? 'Admin' }}</strong></label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="signature_mode" id="sigManual" value="manual" onchange="document.getElementById('signatureNameInput').disabled = false; document.getElementById('signatureNameInput').focus();">
                                                        <label class="form-check-label small" for="sigManual">Manual</label>
                                                    </div>
                                                    <input type="text" name="signature_name" id="signatureNameInput" class="form-control form-control-sm" style="max-width: 200px;" placeholder="Nama signature..." disabled>
                                                </div>
                                                <div class="input-group">
                                                    <textarea name="message" class="form-control" rows="2" placeholder="Tulis balasan..." required></textarea>
                                                    <button type="submit" class="btn btn-primary"><i class="uil uil-message"></i> Kirim</button>
                                                </div>
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
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="min-height: 400px;">
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
    </div>
</div>
@endsection

@section('scripts')
<script>
    var POLL_INTERVAL = 5000; // 5 detik
    var pollUrl = '{{ url('admin/whatsapp/inbox/poll') }}';
    var thread = document.getElementById('chatThread');
    if (thread) { thread.scrollTop = thread.scrollHeight; }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderMessage(msg) {
        var out = msg.direction === 'out';
        return '<div class="d-flex mb-2 ' + (out ? 'justify-content-end' : 'justify-content-start') + '" data-message-id="' + msg.id + '">' +
            '<div class="p-2 rounded ' + (out ? 'bg-primary text-white' : 'bg-light') + '" style="max-width: 70%;">' +
            '<div style="white-space: pre-wrap; word-break: break-word;">' + escapeHtml(msg.body) + '</div>' +
            '<small class="' + (out ? 'text-white-50' : 'text-muted') + '">' + escapeHtml(msg.created_at) + '</small>' +
            '</div></div>';
    }

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
            return '<a href="' + pollUrl.replace('/poll', '') + '?number=' + encodeURIComponent(c.from_number) + '" ' +
                'class="list-group-item list-group-item-action ' + (c.is_selected ? 'active' : '') + '" ' +
                'data-conversation-number="' + escapeHtml(c.from_number) + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div class="me-auto"><h6 class="mb-0">' + escapeHtml(c.title) + '</h6>' +
                '<p class="mb-0 small text-truncate" style="max-width: 180px;">' + escapeHtml(c.preview) + '</p></div>' +
                '<small class="' + (c.is_selected ? 'text-white-50' : 'text-muted') + '">' + escapeHtml(c.time) + '</small>' +
                '</div></a>';
        }).join('');
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
            wrap.classList.add('text-success'); wrap.classList.remove('text-danger');
            text.textContent = 'Terhubung ke Meta Webhook';
        } else {
            dot.classList.add('offline'); dot.classList.remove('online');
            wrap.classList.add('text-danger'); wrap.classList.remove('text-success');
            text.textContent = 'Koneksi terputus';
        }
    }

    function updateWindowStatus(canReply) {
        var replyBox = document.getElementById('replyBox');
        if (!replyBox) return;
        var hasForm = document.getElementById('replyForm');
        if (canReply && !hasForm) {
            location.reload(); // state berubah, reload untuk menampilkan form balas
        } else if (!canReply && hasForm) {
            location.reload();
        }
    }

    setInterval(pollInbox, POLL_INTERVAL);
</script>
@endsection

