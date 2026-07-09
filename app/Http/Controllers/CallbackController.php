<?php

namespace App\Http\Controllers;

use App\Libraries\DuitkuPayment;
use App\Libraries\RouterosAPI;
use App\Libraries\TripayPayment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Report;
use App\Models\Router;
use App\Models\Service;
use App\Models\SmtpSetting;
use App\Models\TemplateMessage;
use App\Models\Website;
use App\Support\WhatsAppNotifier;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function callbackPaydisini(Request $request)
    {
        return response()->json(['error' => 'CallbackPaydisini not found in legacy CI4 controller'], 404);
    }

    public function paydisiniTesting(Request $request)
    {
        return response()->json(['error' => 'PaydisiniTesting not found in legacy CI4 controller'], 404);
    }

    public function tripayCallback(Request $request)
    {
        $provider = PaymentGateway::where('name', 'tripay')->first();
        if (! $provider) {
            return response('Tripay provider not configured', 500);
        }

        $tripay = new TripayPayment($provider->api_url.$provider->url, $provider->code_merchant, $provider->api_key, $provider->private_key, $provider->callback);
        if ($tripay->verifyCallback($provider->callback) !== true) {
            return response('Callback verification failed: '.$tripay->lastError, 400);
        }

        $data = json_decode($request->getContent());
        $event = $_SERVER['HTTP_X_CALLBACK_EVENT'] ?? '';
        if ($event != 'payment_status') {
            return response('Invalid callback event, no action was taken', 400);
        }
        if (($data->status ?? null) != 'PAID') {
            return response()->json(['error' => 'Unrecognized payment status']);
        }

        $uniqueRef = $data->merchant_ref;
        $invoice = Invoice::where('code', $uniqueRef)->where('status', 'Unpaid')->first();
        if (! $invoice) {
            // Sudah diproses (atau tidak ada). Balas sukses agar Tripay berhenti retry.
            return response()->json(['success' => true]);
        }

        date_default_timezone_set('Asia/Jakarta');
        $date = date('Y-m-d');

        if ($invoice->account !== 'user') {
            // Bukan tagihan pelanggan biasa — cukup tandai Paid.
            Invoice::where('code', $uniqueRef)->where('status', 'Unpaid')->update([
                'status' => 'Paid',
                'last_update' => $date,
                'update_by' => 'System Payment Gateway Tripay',
            ]);

            return response()->json(['success' => true]);
        }

        $order = Order::where('idpel', $invoice->idpel)->first();
        $tgl2 = date('Y-m-d', strtotime('+1 month', strtotime((string) $invoice->expdate)));

        // Tangkap status Isolir sebelum transaksi mengubahnya ke Active, dipakai
        // untuk memutuskan kirim notifikasi "buka isolir" setelah pembayaran.
        $wasIsolir = $order && $order->status === 'Isolir';

        // Buka isolir di router bila pelanggan sedang Isolir (port dari CI4:
        // pembayaran online sebelumnya tidak mengembalikan profil normal).
        if ($wasIsolir) {
            $this->reconnectRouter($order, $invoice->package);
        }

        // Tulis pembayaran secara atomik dengan optimistic lock: hanya request
        // pertama (status masih Unpaid) yang mencatat Report + extend order,
        // sehingga callback ganda tidak menggandakan pemasukan.
        $committed = DB::transaction(function () use ($uniqueRef, $invoice, $order, $tgl2, $date) {
            $affected = Invoice::where('code', $uniqueRef)
                ->where('status', 'Unpaid')
                ->update([
                    'status' => 'Paid',
                    'last_update' => $date,
                    'update_by' => 'System Payment Gateway Tripay',
                ]);

            if ($affected === 0) {
                return false;
            }

            Report::insert([
                'category' => 'Pemasukan',
                'jenis_kategori' => 'Pemasukan',
                'balance' => $invoice->received,
                'asal' => 'Pembayaran Online dari '.$invoice->nama.', ID Pelanggan '.$invoice->idpel,
                'date' => $date,
                // Kolom NOT NULL tanpa default (share CI4, strict mode ON di Laravel).
                'account' => '',
                'image' => '',
            ]);

            if ($order) {
                Order::where('idpel', $invoice->idpel)->update([
                    'status' => 'Active',
                    'expdate' => $tgl2,
                ]);
            }

            return true;
        });

        if ($committed) {
            $this->sendPaidNotification($invoice, $order, $wasIsolir);
        }

        return response()->json(['success' => true]);
    }

    public function duitkuCallback(Request $request)
    {
        $provider = PaymentGateway::where('name', 'duitku')->first();
        if (! $provider) {
            return response('Duitku provider not configured', 500);
        }

        $duitku = new DuitkuPayment($provider->api_url.$provider->url, $provider->code_merchant, $provider->api_key, $provider->callback);
        if ($duitku->verifyCallback($provider->callback) !== true) {
            return response('Callback verification failed: '.$duitku->lastError, 400);
        }

        // resultCode '00' = sukses. Selain itu abaikan (mis. pembayaran gagal/pending).
        if (($request->post('resultCode')) !== '00') {
            return response('Unrecognized payment status', 200);
        }

        $uniqueRef = $request->post('merchantOrderId');
        $invoice = Invoice::where('code', $uniqueRef)->where('status', 'Unpaid')->first();
        if (! $invoice) {
            // Sudah diproses (atau tidak ada). Balas sukses agar Duitku berhenti retry.
            return response('SUCCESS', 200);
        }

        date_default_timezone_set('Asia/Jakarta');
        $date = date('Y-m-d');

        if ($invoice->account !== 'user') {
            Invoice::where('code', $uniqueRef)->where('status', 'Unpaid')->update([
                'status' => 'Paid',
                'last_update' => $date,
                'update_by' => 'System Payment Gateway Duitku',
            ]);

            return response('SUCCESS', 200);
        }

        $order = Order::where('idpel', $invoice->idpel)->first();
        $tgl2 = date('Y-m-d', strtotime('+1 month', strtotime((string) $invoice->expdate)));

        // Tangkap status Isolir sebelum transaksi mengubahnya ke Active, dipakai
        // untuk memutuskan kirim notifikasi "buka isolir" setelah pembayaran.
        $wasIsolir = $order && $order->status === 'Isolir';

        if ($wasIsolir) {
            $this->reconnectRouter($order, $invoice->package);
        }

        $committed = DB::transaction(function () use ($uniqueRef, $invoice, $order, $tgl2, $date) {
            $affected = Invoice::where('code', $uniqueRef)
                ->where('status', 'Unpaid')
                ->update([
                    'status' => 'Paid',
                    'last_update' => $date,
                    'update_by' => 'System Payment Gateway Duitku',
                ]);

            if ($affected === 0) {
                return false;
            }

            Report::insert([
                'category' => 'Pemasukan',
                'jenis_kategori' => 'Pemasukan',
                'balance' => $invoice->received,
                'asal' => 'Pembayaran Online dari '.$invoice->nama.', ID Pelanggan '.$invoice->idpel,
                'date' => $date,
                'account' => '',
                'image' => '',
            ]);

            if ($order) {
                Order::where('idpel', $invoice->idpel)->update([
                    'status' => 'Active',
                    'expdate' => $tgl2,
                ]);
            }

            return true;
        });

        if ($committed) {
            $this->sendPaidNotification($invoice, $order, $wasIsolir);
        }

        return response('SUCCESS', 200);
    }

    /**
     * Kembalikan profil PPPoE/hotspot pelanggan ke profil paket (buka isolir)
     * lalu putuskan sesi aktif agar reconnect memakai profil baru. Semua error
     * router ditelan (log) agar callback tetap membalas sukses ke Tripay —
     * pembayaran tetap tercatat walau router sedang tak terjangkau.
     */
    private function reconnectRouter(Order $order, ?string $package): void
    {
        try {
            $router = Router::find($order->id_router);
            if (! $router) {
                return;
            }

            $service = Service::where('paket', $package)->first();
            $ppprofile = $service->ppp_profile ?? null;
            if (! $ppprofile) {
                return;
            }

            $ros = new RouterosAPI;
            if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
                Log::warning("Buka isolir gagal (router tak merespon) idpel {$order->idpel}");

                return;
            }

            $users = $order->pppoe_user;

            if ($order->mode === 'pppoe') {
                $all = $ros->comm('/ppp/secret/getall', [
                    '.proplist' => '.id',
                    '?name' => $users,
                ]);

                if (! empty($all[0]['.id'])) {
                    $ros->comm('/ppp/secret/set', [
                        '.id' => $all[0]['.id'],
                        'profile' => $ppprofile,
                    ]);
                }

                $active = $ros->comm('/ppp/active/getall', [
                    '.proplist' => '.id',
                    '?name' => $users,
                ]);

                if (! empty($active[0]['.id'])) {
                    $ros->comm('/ppp/active/remove', ['.id' => $active[0]['.id']]);
                }
            } elseif ($order->mode === 'hotspot') {
                $ros->comm('/ip/hotspot/user/set', [
                    'numbers' => $users,
                    'profile' => $ppprofile,
                ]);

                $active = $ros->comm('/ip/hotspot/active/print', ['?user' => $users]);
                if (! empty($active)) {
                    foreach ($active as $act) {
                        if (! empty($act['.id'])) {
                            $ros->comm('/ip/hotspot/active/remove', ['.id' => $act['.id']]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Buka isolir error idpel {$order->idpel}: {$e->getMessage()}");
        }
    }

    /**
     * Kirim notifikasi "tagihan terbayar" via WhatsApp + email Brevo. Semua
     * dibungkus try/catch agar kegagalan notif tidak mempengaruhi status callback.
     */
    private function sendPaidNotification(Invoice $invoice, ?Order $order, bool $wasIsolir = false): void
    {
        $template = TemplateMessage::all()->last();
        $message = $template->notif_tagihan_terbayar ?? '';
        $pesanemail = $template->notif_tagihan_terbayar_email ?? '';
        $baseUrl = url('/').'/';

        $idpel = $invoice->idpel;
        $code = $invoice->code;
        // Template Meta notif_tagihan_terbayar butuh 4 parameter [id_pelanggan, no_invoice, link, paket];
        // paket diambil dari invoice (fallback order->paket) agar jumlah parameter cocok — Meta menolak
        // pesan bila jumlahnya tidak sama dengan definisi template.
        $package = $invoice->package ?: ($order->paket ?? '-');

        $message = str_replace(['{id_pelanggan}', '{nomor_invoice}', '{link_web}'], [$idpel, $code, $baseUrl], $message);
        $pesanemail = str_replace(['{id_pelanggan}', '{nomor_invoice}', '{link_web}'], [$idpel, $code, $baseUrl], $pesanemail);

        $nomor = $order->nomor ?? '';
        if ($nomor !== '') {
            try {
                WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_TERBAYAR, $nomor, $message, [$idpel, $code, $baseUrl, $package]);
            } catch (\Throwable $e) {
                Log::warning("Gagal kirim WhatsApp terbayar (callback) #{$code} ({$idpel}): {$e->getMessage()}");
            }

            if ($wasIsolir) {
                try {
                    $nama = $order->nama ?? $invoice->nama;
                    $bukaMessage = "Layanan Internet Anda Telah Aktif Kembali\n\nNama: {$nama}\nID Pelanggan: {$idpel}\nPaket: {$package}\nTerima kasih atas pembayaran Anda.\nLink: {$baseUrl}\n\nSalam Hangat\n\nANNORTY NET";

                    // Template Meta notif_buka_isolir: [nama, id_pelanggan, paket, link]
                    WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_BUKA_ISOLIR, $nomor, $bukaMessage, [
                        $nama,
                        $idpel,
                        $package,
                        $baseUrl,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Gagal kirim WhatsApp buka isolir (callback) #{$code} ({$idpel}): {$e->getMessage()}");
                }
            }
        }

        $email = $order->email ?? '';
        if ($email === '') {
            return;
        }

        try {
            $website = Website::first();
            $smtp = SmtpSetting::all()->last();

            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $smtp->key ?? '');
            $apiInstance = new TransactionalEmailsApi(new Client, $config);

            $sendSmtpEmail = new SendSmtpEmail;
            $sendSmtpEmail['params'] = ['subject' => 'Tagihan Internet '.($website->title ?? '').' Telah Terbayar - #'.$code.' '];
            $sendSmtpEmail['subject'] = '{{params.subject}}';
            $sendSmtpEmail['htmlContent'] = view('emails.new-customer', [
                'logo' => $website->logo ?? '',
                'titletext' => $website->title ?? '',
                'pesanemail' => $pesanemail,
            ])->render();
            $sendSmtpEmail['sender'] = ['name' => $smtp->nama ?? '', 'email' => $smtp->email ?? ''];
            $sendSmtpEmail['to'] = [['email' => $email]];

            $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (\Throwable $e) {
            Log::warning("Gagal kirim email terbayar (callback) #{$code} ({$idpel}): {$e->getMessage()}");
        }
    }
}
