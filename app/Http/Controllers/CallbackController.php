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

        $uniqueRef = trim((string) ($data->merchant_ref ?? ''));
        if ($uniqueRef === '') {
            return response('Invalid merchant reference', 400);
        }

        $result = $this->commitGatewayPayment($uniqueRef, 'Tripay', [
            'provider_reference' => (string) ($data->reference ?? ''),
            'amount' => (string) ($data->total_amount ?? ''),
        ]);
        if ($result['committed']) {
            $routerRestored = ! $result['was_isolir']
                || $this->restoreGatewayCustomerAccess($result['order'], $result['invoice']);
            $this->sendPaidNotificationSafely(
                $result['invoice'],
                $result['order'],
                $result['was_isolir'] && $routerRestored
            );
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

        $uniqueRef = trim((string) $request->post('merchantOrderId'));
        if ($uniqueRef === '') {
            return response('Invalid merchant reference', 400);
        }

        $result = $this->commitGatewayPayment($uniqueRef, 'Duitku', [
            'provider_reference' => (string) $request->post('reference'),
            'amount' => (string) $request->post('amount'),
        ]);
        if ($result['committed']) {
            $routerRestored = ! $result['was_isolir']
                || $this->restoreGatewayCustomerAccess($result['order'], $result['invoice']);
            $this->sendPaidNotificationSafely(
                $result['invoice'],
                $result['order'],
                $result['was_isolir'] && $routerRestored
            );
        }

        return response('SUCCESS', 200);
    }

    /**
     * Transition callback dibuat atomik dan memakai advisory lock invoice yang
     * sama dengan transaction creator/manual takeover.
     *
     * @param  array<string, string>  $callbackContext
     * @return array{committed: bool, invoice: ?Invoice, order: ?Order, was_isolir: bool}
     */
    protected function commitGatewayPayment(string $invoiceCode, string $providerName, array $callbackContext): array
    {
        if (! Invoice::acquirePaymentLock($invoiceCode)) {
            // Provider akan retry callback ketika endpoint tidak mengembalikan 2xx,
            // tetapi controller legacy harus tetap cepat. Exception menghasilkan 500.
            throw new \RuntimeException("Invoice #{$invoiceCode} sedang diproses.");
        }

        try {
            return DB::transaction(function () use ($invoiceCode, $providerName, $callbackContext) {
                $invoice = Invoice::where('code', $invoiceCode)->lockForUpdate()->first();
                if (! $invoice) {
                    return ['committed' => false, 'invoice' => null, 'order' => null, 'was_isolir' => false];
                }

                if ($invoice->status !== 'Unpaid') {
                    $callbackReference = (string) ($callbackContext['provider_reference'] ?? '');
                    $matchesStoredProviderTransaction = $invoice->status === 'Error'
                        && (string) $invoice->reference !== ''
                        && $callbackReference !== ''
                        && hash_equals((string) $invoice->reference, $callbackReference);

                    if ($matchesStoredProviderTransaction) {
                        Log::critical('Callback pembayaran datang setelah manual takeover.', [
                            'invoice_code' => $invoiceCode,
                            'idpel' => $invoice->idpel,
                            'provider' => $providerName,
                            'stored_reference' => $invoice->reference,
                            'callback_reference' => $callbackReference,
                            'callback_amount' => $callbackContext['amount'] ?? '',
                        ]);
                    }

                    return ['committed' => false, 'invoice' => $invoice, 'order' => null, 'was_isolir' => false];
                }

                $date = now('Asia/Jakarta')->toDateString();
                if ($invoice->account !== 'user') {
                    $invoice->update([
                        'status' => 'Paid',
                        'last_update' => $date,
                        'update_by' => 'System Payment Gateway '.$providerName,
                    ]);

                    return ['committed' => true, 'invoice' => $invoice->fresh(), 'order' => null, 'was_isolir' => false];
                }

                $order = Order::where('idpel', $invoice->idpel)->lockForUpdate()->first();
                $wasIsolir = $order?->status === 'Isolir';
                $newExpiration = date('Y-m-d', strtotime('+1 month', strtotime((string) $invoice->expdate)));
                if ($order && strtotime((string) $order->expdate) > strtotime($newExpiration)) {
                    // Pembayaran invoice periode lama tidak boleh memundurkan
                    // expiration yang sudah diperpanjang callback lain.
                    $newExpiration = (string) $order->expdate;
                }

                $invoice->update([
                    'status' => 'Paid',
                    'last_update' => $date,
                    'update_by' => 'System Payment Gateway '.$providerName,
                ]);

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
                    $order->update([
                        'status' => 'Active',
                        'expdate' => $newExpiration,
                    ]);
                }

                return [
                    'committed' => true,
                    'invoice' => $invoice->fresh(),
                    'order' => $order?->fresh(),
                    'was_isolir' => $wasIsolir,
                ];
            });
        } finally {
            try {
                Invoice::releasePaymentLock($invoiceCode);
            } catch (\Throwable $e) {
                Log::warning("Gagal melepas callback payment lock #{$invoiceCode}: {$e->getMessage()}");
            }
        }
    }

    protected function restoreGatewayCustomerAccess(?Order $order, ?Invoice $invoice): bool
    {
        if (! $order || ! $invoice || $order->status !== 'Active') {
            return true;
        }

        // Hanya order yang tadinya Isolir membutuhkan RouterOS restoration.
        // Caller memakai return ini hanya bersama flag was_isolir.
        $accessLock = Invoice::customerAccessLockName((string) $order->idpel);
        $lockAcquired = false;

        try {
            $lockAcquired = Invoice::acquireNamedLock($accessLock);
            if (! $lockAcquired) {
                $this->queueCustomerAccessRestore($order);

                return false;
            }

            $freshOrder = Order::whereKey($order->id)->first();
            if (! $freshOrder || $freshOrder->status !== 'Active' || (string) $freshOrder->expdate !== (string) $order->expdate) {
                return false;
            }

            $restored = $this->reconnectRouter($freshOrder, $invoice->package);
            if (! $restored) {
                $this->queueCustomerAccessRestore($freshOrder);
            }

            return $restored;
        } catch (\Throwable $e) {
            Log::warning("Post-payment RouterOS callback gagal ({$order->idpel}): {$e->getMessage()}");
            $this->queueCustomerAccessRestore($order);

            return false;
        } finally {
            if ($lockAcquired) {
                try {
                    Invoice::releaseNamedLock($accessLock);
                } catch (\Throwable $e) {
                    Log::warning("Gagal melepas customer access lock callback ({$order->idpel}): {$e->getMessage()}");
                }
            }
        }
    }

    private function queueCustomerAccessRestore(Order $order): void
    {
        try {
            Order::whereKey($order->id)
                ->where('status', 'Active')
                ->where('expdate', $order->expdate)
                ->update(['status' => 'Isolir']);
        } catch (\Throwable $e) {
            Log::error("Gagal menandai retry RouterOS callback ({$order->idpel}): {$e->getMessage()}");
        }
    }

    /**
     * Kembalikan profil PPPoE/hotspot pelanggan ke profil paket (buka isolir)
     * lalu putuskan sesi aktif agar reconnect memakai profil baru.
     */
    protected function reconnectRouter(Order $order, ?string $package): bool
    {
        $ros = null;

        try {
            $router = Router::find($order->id_router);
            if (! $router) {
                return false;
            }

            $service = Service::where('paket', $package)->first();
            $ppprofile = $service->ppp_profile ?? null;
            if (! $ppprofile) {
                return false;
            }

            $ros = $this->makeRouteros();
            if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
                Log::warning("Buka isolir gagal (router tak merespon) idpel {$order->idpel}");

                return false;
            }

            $users = $order->pppoe_user;

            if ($order->mode === 'pppoe') {
                $all = $ros->comm('/ppp/secret/getall', [
                    '.proplist' => '.id',
                    '?name' => $users,
                ]);
                $secretId = is_array($all) ? ($all[0]['.id'] ?? null) : null;
                if (! $secretId) {
                    throw new \RuntimeException('PPPoE secret tidak ditemukan.');
                }

                $this->ensureRouterCommandSucceeded($ros->comm('/ppp/secret/set', [
                    '.id' => $secretId,
                    'profile' => $ppprofile,
                ]));

                $active = $ros->comm('/ppp/active/getall', [
                    '.proplist' => '.id',
                    '?name' => $users,
                ]);
                foreach (is_array($active) ? $active : [] as $session) {
                    if (! empty($session['.id'])) {
                        $this->ensureRouterCommandSucceeded($ros->comm('/ppp/active/remove', ['.id' => $session['.id']]));
                    }
                }
            } elseif ($order->mode === 'hotspot') {
                $this->ensureRouterCommandSucceeded($ros->comm('/ip/hotspot/user/set', [
                    'numbers' => $users,
                    'profile' => $ppprofile,
                ]));

                $active = $ros->comm('/ip/hotspot/active/print', ['?user' => $users]);
                foreach (is_array($active) ? $active : [] as $session) {
                    if (! empty($session['.id'])) {
                        $this->ensureRouterCommandSucceeded($ros->comm('/ip/hotspot/active/remove', ['.id' => $session['.id']]));
                    }
                }
            } else {
                throw new \RuntimeException("Mode pelanggan tidak didukung: {$order->mode}");
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("Buka isolir error idpel {$order->idpel}: {$e->getMessage()}");

            return false;
        } finally {
            try {
                $ros?->disconnect();
            } catch (\Throwable $e) {
                Log::warning("Disconnect RouterOS callback gagal ({$order->idpel}): {$e->getMessage()}");
            }
        }
    }

    protected function makeRouteros(): RouterosAPI
    {
        return new RouterosAPI;
    }

    private function ensureRouterCommandSucceeded(mixed $response): void
    {
        if (is_array($response) && (isset($response['!trap']) || isset($response['!fatal']))) {
            $message = $response['!trap'][0]['message'] ?? $response['!fatal'][0]['message'] ?? 'Perintah RouterOS gagal.';

            throw new \RuntimeException($message);
        }
    }

    private function sendPaidNotificationSafely(Invoice $invoice, ?Order $order, bool $wasIsolir = false): void
    {
        try {
            $this->sendPaidNotification($invoice, $order, $wasIsolir);
        } catch (\Throwable $e) {
            Log::warning("Notifikasi callback gagal #{$invoice->code} ({$invoice->idpel}): {$e->getMessage()}");
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

        $message = str_replace(['{id_pelanggan}', '{nomor_invoice}', '{link_web}', '{link_bayar}', '{link_invoice}'], [$idpel, $code, $baseUrl, url('tagihan/'.$code), url('invoice/'.$code)], $message);
        $pesanemail = str_replace(['{id_pelanggan}', '{nomor_invoice}', '{link_web}'], [$idpel, $code, $baseUrl], $pesanemail);

        $nomor = $order->nomor ?? '';
        if ($nomor !== '') {
            try {
                WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_TERBAYAR, $nomor, $message, [$idpel, $code, url('invoice/'.$code), $package]);
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
