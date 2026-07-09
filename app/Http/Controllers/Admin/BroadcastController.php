<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\WhatsAppApi;
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
        $getAccount = User::where('status_account', 'Active')->get();
        $gateway = WhatsAppGatewayResolver::active();

        $message = (string) $request->input('message');

        // Mengubah format HTML ke format WhatsApp
        $text = strip_tags($message);
        $text = str_replace('<br>', "\n", $text);
        $text = str_replace('&nbsp;', "\n", $text);

        if (! $gateway) {
            return redirect('admin/cms/broadcast/whatsapp')->with('auth_errors', ['Whatsapp Gateway Mode Blast tidak aktif ! ']);
        }

        foreach ($getAccount as $dataAccount) {
            WhatsAppNotifier::sendText($dataAccount->nomor, $text);
        }

        return redirect('admin/cms/broadcast/whatsapp')->with('success', ['Pengiriman berhasil : Sukses mengirimkan broadcast ']);
    }

    // POST admin/cms/broadcast/sendmedia (CI4: AdminController::sendbroadcastmedia -> WhatsappController::sendbroadcastmedia)
    // Catatan: method asli di CI4 kosong (belum diimplementasi). Di-port sebagai kirim media
    // via WhatsAppApi::sendMessageMedia ke semua akun aktif, mengikuti form pada view broadcast whatsapp.
    public function sendbroadcastmedia(Request $request)
    {
        $getAccount = User::where('status_account', 'Active')->get();
        $gateway = WhatsAppGatewayResolver::active();

        $url = $request->input('url');
        $type = $request->input('type');
        $caption = (string) $request->input('caption');

        if (! $gateway) {
            return redirect('admin/cms/broadcast/whatsapp')->with('auth_errors', ['Whatsapp Gateway Mode Blast tidak aktif ! ']);
        }

        foreach ($getAccount as $dataAccount) {
            WhatsAppNotifier::sendMedia($dataAccount->nomor, $type, $caption, $url);
        }

        return redirect('admin/cms/broadcast/whatsapp')->with('success', ['Pengiriman berhasil : Sukses mengirimkan broadcast media ']);
    }
}
