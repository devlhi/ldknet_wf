<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Models\Website;
use App\Support\WhatsAppGatewayResolver;
use App\Support\WhatsAppNotifier;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    // GET admin/cms/broadcast (CI4: AdminController::broadcast -> SmtpController::broadcast)
    public function broadcast()
    {
        return view('admin.cms.email-broadcast', [
            'title' => 'Broadcast Information',
        ] + $this->websiteData());
    }

    // GET admin/cms/broadcast/whatsapp (CI4: AdminController::broadcastwhatsapp -> WhatsappController::broadcast)
    public function broadcastwhatsapp()
    {
        return view('admin.cms.whatsapp-broadcast', [
            'title' => 'Broadcast Information',
        ] + $this->websiteData());
    }

    // POST admin/cms/broadcast/email/send (CI4: AdminController::sendbroadcast -> SmtpController::sendbroadcast)
    public function sendbroadcast(Request $request)
    {
        $getAccount = User::where('status_account', 'Active')->get();
        $smtp = SmtpSetting::all()->last();

        $message = (string) $request->input('message');
        $subject = $request->input('subject');

        $key = $smtp->key ?? '';
        $name = $smtp->nama ?? '';
        $email = $smtp->email ?? '';

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $key);
        $apiInstance = new TransactionalEmailsApi(new Client, $config);

        $html = view('emails.broadcast', [
            'message' => $message,
        ])->render();

        foreach ($getAccount as $dataAccount) {
            $emailcustomer = $dataAccount->email;

            $sendSmtpEmail = new SendSmtpEmail;
            $sendSmtpEmail['params'] = ['subject' => $subject];
            $sendSmtpEmail['subject'] = '{{params.subject}}';
            $sendSmtpEmail['htmlContent'] = $html;
            $sendSmtpEmail['sender'] = ['name' => $name, 'email' => $email];
            $sendSmtpEmail['to'] = [['email' => $emailcustomer]];
            $sendSmtpEmail['replyTo'] = ['email' => $email, 'name' => $name];

            try {
                $apiInstance->sendTransacEmail($sendSmtpEmail);
            } catch (\Throwable $e) {
                return redirect('admin/cms/broadcast')->with('auth_errors', ['Gagal, ada kesalahan pada sistem saat mengirim email']);
            }
        }

        return redirect('admin/cms/broadcast')->with('success', ['Pengiriman berhasil : Sukses mengirimkan broadcast']);
    }

    // POST admin/cms/broadcast/whatsapp/send (CI4: AdminController::sendbroadcastwhatsapp -> WhatsappController::sendbroadcast)
    public function sendbroadcastwhatsapp(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);
        $getAccount = User::where('status_account', 'Active')->get();
        $gateway = WhatsAppGatewayResolver::active();

        $text = html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $validated['message'])));

        if (! $gateway) {
            return redirect('admin/cms/broadcast/whatsapp')->with('auth_errors', ['Whatsapp Gateway Mode Blast tidak aktif ! ']);
        }

        [$sent, $failed] = $this->broadcastResults($getAccount, function (User $account) use ($text) {
            return WhatsAppNotifier::sendText($account->nomor, $text, false, true);
        }, $gateway);

        return $this->broadcastRedirect('broadcast', $sent, $failed);
    }

    // POST admin/cms/broadcast/sendmedia (CI4: AdminController::sendbroadcastmedia -> WhatsappController::sendbroadcastmedia)
    // Catatan: method asli di CI4 kosong (belum diimplementasi). Di-port sebagai kirim media
    // via WhatsAppApi::sendMessageMedia ke semua akun aktif, mengikuti form pada view broadcast whatsapp.
    public function sendbroadcastmedia(Request $request)
    {
        $validated = $request->validate([
            'url' => ['required', 'url:https', 'max:2048'],
            'type' => ['required', 'string', 'in:image,video,audio,document'],
            'caption' => ['nullable', 'string', 'max:1024'],
        ]);
        $getAccount = User::where('status_account', 'Active')->get();
        $gateway = WhatsAppGatewayResolver::active();

        if (! $gateway) {
            return redirect('admin/cms/broadcast/whatsapp')->with('auth_errors', ['Whatsapp Gateway Mode Blast tidak aktif ! ']);
        }

        [$sent, $failed] = $this->broadcastResults($getAccount, function (User $account) use ($validated) {
            return WhatsAppNotifier::sendMedia(
                $account->nomor,
                $validated['type'],
                (string) ($validated['caption'] ?? ''),
                $validated['url'],
                false
            );
        }, $gateway);

        return $this->broadcastRedirect('broadcast media', $sent, $failed);
    }

    private function broadcastResults($accounts, callable $send, $gateway): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                WhatsAppNotifier::responseSucceeded($send($account), $gateway) ? $sent++ : $failed++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return [$sent, $failed];
    }

    private function broadcastRedirect(string $label, int $sent, int $failed)
    {
        $message = ucfirst($label)." selesai. Berhasil: {$sent}, gagal: {$failed}.";
        $key = $failed > 0 ? 'auth_errors' : 'success';

        return redirect('admin/cms/broadcast/whatsapp')->with($key, [$message]);
    }
}
