<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaInboxMessage;
use App\Models\Website;
use App\Support\WhatsAppGatewayResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppInboxController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function index(Request $request)
    {
        $conversations = $this->conversations();
        $selectedNumber = (string) ($request->query('number') ?: '');
        $messages = collect();
        $customer = null;
        $lastIncomingAt = null;

        if ($selectedNumber !== '') {
            $messages = WaInboxMessage::where('from_number', $selectedNumber)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $customer = User::where('nomor', $selectedNumber)
                ->orWhere('nomor', '0'.substr($selectedNumber, 2))
                ->first();

            $lastIncomingAt = WaInboxMessage::where('from_number', $selectedNumber)
                ->where('direction', 'in')
                ->latest('created_at')
                ->first()?->created_at;
        }

        return view('admin.gateway.whatsapp.inbox', [
            'title' => 'WhatsApp Inbox',
            'conversations' => $conversations,
            'selectedNumber' => $selectedNumber,
            'messages' => $messages,
            'customer' => $customer,
            'lastIncomingAt' => $lastIncomingAt,
            'canReplyText' => $this->replyWindowIsOpen($lastIncomingAt),
        ] + $this->websiteData());
    }

    public function poll(Request $request)
    {
        $number = (string) $request->query('number', '');
        $afterId = (int) $request->query('after_id', 0);

        $messages = collect();
        $canReplyText = false;

        if ($number !== '') {
            $messages = WaInboxMessage::where('from_number', $number)
                ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $lastIncomingAt = WaInboxMessage::where('from_number', $number)
                ->where('direction', 'in')
                ->latest('created_at')
                ->first()?->created_at;

            $canReplyText = $this->replyWindowIsOpen($lastIncomingAt);
        }

        return response()->json([
            'messages' => $messages->map(fn ($msg) => [
                'id' => $msg->id,
                'direction' => $msg->direction,
                'body' => $msg->body,
                'status' => $msg->status,
                'message_type' => $msg->message_type,
                'media_url' => $msg->hasMedia() ? url('admin/whatsapp/inbox/media/'.$msg->id) : null,
                'can_reply' => $msg->direction === 'in' && (string) $msg->meta_message_id !== '',
                'created_at' => optional($msg->created_at)->format('H:i'),
            ])->values(),
            'can_reply_text' => $canReplyText,
            'conversations' => $this->conversations()->map(fn ($conv) => [
                'from_number' => $conv->from_number,
                'title' => $conv->customer_name ?: ($conv->from_name ?: $conv->from_number),
                'preview' => ($conv->direction === 'out' ? 'Anda: ' : '').$conv->body,
                'time' => optional($conv->created_at)->format('H:i'),
                'is_selected' => $conv->from_number === $number,
            ])->values(),
        ]);
    }

    public function media(WaInboxMessage $message)
    {
        abort_unless($message->message_type === 'image' && $message->hasMedia(), 404);

        $path = WaInboxMessage::mediaPath((string) $message->meta_message_id);
        $contents = Storage::disk('local')->get($path);
        $imageInfo = @getimagesizefromstring($contents);
        abort_unless($imageInfo !== false && isset($imageInfo['mime']) && str_starts_with($imageInfo['mime'], 'image/'), 404);

        return response($contents, 200, [
            'Content-Type' => $imageInfo['mime'],
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function sendImage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:1024'],
        ]);

        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway || ! WhatsAppGatewayResolver::isMeta($gateway)) {
            return back()->with('auth_errors', ['Gateway WhatsApp Meta aktif tidak ditemukan.']);
        }

        $lastIncomingAt = WaInboxMessage::where('from_number', $validated['number'])
            ->where('direction', 'in')
            ->latest('created_at')
            ->first()?->created_at;
        if (! $this->replyWindowIsOpen($lastIncomingAt)) {
            return back()->with('auth_errors', ['Gambar hanya bisa dikirim dalam 24 jam sejak pesan terakhir user. Gunakan template message untuk percakapan lama.']);
        }

        $image = $request->file('image');
        $imageInfo = @getimagesize($image->getRealPath());
        $mimeType = strtolower((string) data_get($imageInfo, 'mime', ''));
        if ($imageInfo === false || ! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            return back()->withInput()->with('auth_errors', ['File harus berupa gambar JPEG atau PNG yang valid.']);
        }

        $contents = file_get_contents($image->getRealPath());
        if ($contents === false) {
            return back()->withInput()->with('auth_errors', ['Gambar tidak dapat dibaca.']);
        }

        $caption = trim((string) ($validated['caption'] ?? ''));
        $api = WhatsAppGatewayResolver::make($gateway);
        $sender = WhatsAppGatewayResolver::sender($gateway);

        try {
            $upload = $api->uploadMedia($sender, $contents, $mimeType, $mimeType === 'image/png' ? 'reply.png' : 'reply.jpg');
            $mediaId = (string) data_get($upload, 'id', '');
            if ($mediaId === '') {
                return back()->withInput()->with('auth_errors', ['Meta menolak upload gambar.']);
            }

            $response = $api->sendImageByMediaId($sender, $validated['number'], $mediaId, $caption);
            $responseJson = json_decode($response, true);
            $metaMessageId = (string) data_get($responseJson, 'messages.0.id', '');
            if (! is_array($responseJson) || isset($responseJson['error']) || $metaMessageId === '') {
                return back()->withInput()->with('auth_errors', ['Meta gagal mengirim gambar.']);
            }
        } catch (\Throwable) {
            return back()->withInput()->with('auth_errors', ['Gambar gagal dikirim ke Meta. Silakan coba lagi.']);
        }

        $message = WaInboxMessage::create([
            'from_number' => $validated['number'],
            'from_name' => User::where('nomor', $validated['number'])->value('nama'),
            'direction' => 'out',
            'body' => trim('[Image] '.$caption),
            'message_type' => 'image',
            'meta_message_id' => $metaMessageId,
            'status' => 'sent',
            'sent_by' => auth()->id(),
            'created_at' => now(),
        ]);

        if (! Storage::disk('local')->put(WaInboxMessage::mediaPath($metaMessageId), $contents)) {
            Log::warning('Salinan lokal gambar WhatsApp keluar gagal disimpan', [
                'message_id_hash' => hash('sha256', $metaMessageId),
                'inbox_message_id' => $message->id,
            ]);

            return redirect('admin/whatsapp/inbox?number='.$validated['number'])
                ->with('success', ['Gambar berhasil dikirim ke pelanggan, tetapi preview lokal gagal disimpan.']);
        }

        return redirect('admin/whatsapp/inbox?number='.$validated['number'])->with('success', ['Gambar berhasil dikirim.']);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:4000'],
            'signature_mode' => ['nullable', 'string', 'in:auto,manual'],
            'signature_name' => ['nullable', 'string', 'max:80', 'required_if:signature_mode,manual'],
            'reply_to_message_id' => ['nullable', 'integer'],
        ]);

        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway || ! WhatsAppGatewayResolver::isMeta($gateway)) {
            return back()->with('auth_errors', ['Gateway WhatsApp Meta aktif tidak ditemukan.']);
        }

        $lastIncomingAt = WaInboxMessage::where('from_number', $validated['number'])
            ->where('direction', 'in')
            ->latest('created_at')
            ->first()?->created_at;

        if (! $this->replyWindowIsOpen($lastIncomingAt)) {
            return back()->with('auth_errors', ['Balasan teks bebas hanya bisa dikirim dalam 24 jam sejak pesan terakhir user. Gunakan template message untuk percakapan lama.']);
        }

        $replyTo = null;
        if (! empty($validated['reply_to_message_id'])) {
            $replyTo = WaInboxMessage::whereKey($validated['reply_to_message_id'])
                ->where('from_number', $validated['number'])
                ->where('direction', 'in')
                ->whereNotNull('meta_message_id')
                ->where('meta_message_id', '!=', '')
                ->first();

            if (! $replyTo) {
                return back()->withInput()->with('auth_errors', ['Pesan yang akan dibalas tidak valid atau bukan dari percakapan ini.']);
            }
        }

        $signatureMode = $validated['signature_mode'] ?? 'auto';
        $signatureName = $signatureMode === 'manual'
            ? trim((string) $validated['signature_name'])
            : trim((string) (auth()->user()->nama ?? auth()->user()->name ?? 'Admin'));
        $messageBody = trim($validated['message'])."\n\n~".$signatureName;

        if (mb_strlen($messageBody) > 4000) {
            return back()->withInput()->with('auth_errors', ['Pesan terlalu panjang setelah ditambahkan signature admin.']);
        }

        try {
            $api = WhatsAppGatewayResolver::make($gateway);
            $response = $api->sendMessage(
                WhatsAppGatewayResolver::sender($gateway),
                $validated['number'],
                $messageBody,
                $replyTo?->meta_message_id
            );
            $responseJson = json_decode((string) $response, true);
            $metaMessageId = (string) data_get($responseJson, 'messages.0.id', '');
            if (! is_array($responseJson) || isset($responseJson['error']) || $metaMessageId === '') {
                return back()->withInput()->with('auth_errors', ['Meta gagal mengirim pesan. Silakan coba lagi.']);
            }
        } catch (\Throwable) {
            return back()->withInput()->with('auth_errors', ['Pesan gagal dikirim ke Meta. Silakan coba lagi.']);
        }

        WaInboxMessage::create([
            'from_number' => $validated['number'],
            'from_name' => User::where('nomor', $validated['number'])->value('nama'),
            'direction' => 'out',
            'body' => $messageBody,
            'message_type' => 'text',
            'meta_message_id' => $metaMessageId,
            'status' => 'sent',
            'sent_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect('admin/whatsapp/inbox?number='.$validated['number'])
            ->with('success', ['Pesan berhasil dikirim.'])
            ->with('wa_text_sent', true);
    }

    private function replyWindowIsOpen($lastIncomingAt): bool
    {
        return $lastIncomingAt !== null && $lastIncomingAt->greaterThan(now()->subDay());
    }

    private function conversations()
    {
        $latestIds = WaInboxMessage::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('from_number');

        return WaInboxMessage::query()
            ->from('wa_inbox_messages as m')
            ->joinSub($latestIds, 'latest', fn ($join) => $join->on('m.id', '=', 'latest.id'))
            ->leftJoin('users as u', function ($join) {
                $join->on('u.nomor', '=', 'm.from_number')
                    ->orOn('u.nomor', '=', DB::raw("CONCAT('0', SUBSTRING(m.from_number, 3))"));
            })
            ->select('m.*', 'u.nama as customer_name')
            ->orderByDesc('m.created_at')
            ->get();
    }
}
