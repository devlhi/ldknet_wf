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
        $selectedNumber = (string) ($request->query('number') ?: data_get($conversations->first(), 'from_number', ''));
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
            'canReplyText' => $lastIncomingAt ? $lastIncomingAt->diffInHours(now()) < 24 : false,
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

            $canReplyText = $lastIncomingAt ? $lastIncomingAt->diffInHours(now()) < 24 : false;
        }

        return response()->json([
            'messages' => $messages->map(fn ($msg) => [
                'id' => $msg->id,
                'direction' => $msg->direction,
                'body' => $msg->body,
                'status' => $msg->status,
                'message_type' => $msg->message_type,
                'created_at' => optional($msg->created_at)->format('d M H:i'),
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

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:4000'],
            'signature_mode' => ['nullable', 'string', 'in:auto,manual'],
            'signature_name' => ['nullable', 'string', 'max:80', 'required_if:signature_mode,manual'],
        ]);

        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway || ! WhatsAppGatewayResolver::isMeta($gateway)) {
            return back()->with('auth_errors', ['Gateway WhatsApp Meta aktif tidak ditemukan.']);
        }

        $lastIncomingAt = WaInboxMessage::where('from_number', $validated['number'])
            ->where('direction', 'in')
            ->latest('created_at')
            ->first()?->created_at;

        if (! $lastIncomingAt || $lastIncomingAt->diffInHours(now()) >= 24) {
            return back()->with('auth_errors', ['Balasan teks bebas hanya bisa dikirim dalam 24 jam sejak pesan terakhir user. Gunakan template message untuk percakapan lama.']);
        }

        $signatureMode = $validated['signature_mode'] ?? 'auto';
        $signatureName = $signatureMode === 'manual'
            ? trim((string) $validated['signature_name'])
            : trim((string) (auth()->user()->nama ?? auth()->user()->name ?? 'Admin'));
        $messageBody = trim($validated['message'])."\n\n~".$signatureName;

        if (mb_strlen($messageBody) > 4000) {
            return back()->withInput()->with('auth_errors', ['Pesan terlalu panjang setelah ditambahkan signature admin.']);
        }

        $api = WhatsAppGatewayResolver::make($gateway);
        $response = $api->sendMessage(WhatsAppGatewayResolver::sender($gateway), $validated['number'], $messageBody);
        $responseJson = json_decode((string) $response, true);

        if (is_array($responseJson) && isset($responseJson['error'])) {
            $errorMessage = data_get($responseJson, 'error.message', 'Meta API menolak pesan.');

            return back()->withInput()->with('auth_errors', ['Gagal mengirim pesan: '.$errorMessage]);
        }

        WaInboxMessage::create([
            'from_number' => $validated['number'],
            'from_name' => User::where('nomor', $validated['number'])->value('nama'),
            'direction' => 'out',
            'body' => $messageBody,
            'message_type' => 'text',
            'status' => 'sent',
            'sent_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect('admin/whatsapp/inbox?number='.$validated['number'])->with('success', ['Pesan berhasil dikirim.']);
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
