<?php

namespace App\Http\Controllers;

use App\Libraries\RouterosAPI;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Router;
use App\Models\Service;
use App\Models\TemplateMessage;
use App\Support\WhatsAppNotifier;
use DateTime;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AutoController extends Controller
{
    public function updatestatus()
    {
        // Kirim notifikasi WhatsApp tepat saat transisi Active -> Isolir (bukan di
        // isolir() yang looping tiap cron, agar tidak spam). Update per baris supaya
        // status hanya berubah setelah percobaan notif; kegagalan WA tidak menghalangi.
        foreach (Order::whereRaw('DATEDIFF(CURDATE(), expdate) >= 1')->where('status', 'Active')->get() as $order) {
            $nomor = (string) ($order->nomor ?? '');
            if ($nomor !== '') {
                try {
                    $jatuhTempo = tanggal_indo(date('Y-m-d', strtotime((string) $order->expdate)));
                    // Referensi bayar: kode invoice Unpaid kalau ada, kalau tidak pakai
                    // ID Pelanggan (selalu ada). Halaman /tagihan/{ref} resolve keduanya,
                    // jadi tombol dinamis {{1}} tidak pernah kosong (Meta tidak menolak).
                    $unpaidInvoice = Invoice::where('idpel', $order->idpel)->where('status', 'Unpaid')->orderByDesc('id')->first();
                    $payRef = $unpaidInvoice?->code ?? $order->idpel;
                    $link = url('tagihan/'.$payRef);
                    $message = "Layanan Internet Anda Dinonaktifkan Sementara\n\nNama: {$order->nama}\nID Pelanggan: {$order->idpel}\nJatuh Tempo: {$jatuhTempo}\nMohon segera lakukan pembayaran untuk mengaktifkan kembali layanan.\nLink: {$link}\nPaket: {$order->paket}\n\nSalam Hangat\n\nANNORTY NET";

                    // Template Meta notif_isolir: [nama, id_pelanggan, jatuh_tempo, link, paket]
                    WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_ISOLIR, $nomor, $message, [
                        $order->nama,
                        $order->idpel,
                        $jatuhTempo,
                        $link,
                        $order->paket,
                    ], $payRef);
                } catch (\Throwable $e) {
                    Log::warning("Gagal kirim WhatsApp isolir ({$order->idpel}): {$e->getMessage()}");
                }
            }

            Order::where('id', $order->id)->where('status', 'Active')->update(['status' => 'Isolir']);
        }
    }

    public function isolir()
    {
        $attempted = 0;
        $succeeded = 0;
        $failed = 0;

        Order::where('status', 'Isolir')->orderBy('id')->chunkById(100, function ($orders) use (&$attempted, &$succeeded, &$failed) {
            foreach ($orders as $snapshot) {
                $attempted++;
                $accessLock = Invoice::customerAccessLockName((string) $snapshot->idpel);
                $lockAcquired = false;
                $ros = null;
                $router = null;

                try {
                    $lockAcquired = Invoice::acquireNamedLock($accessLock);
                    if (! $lockAcquired) {
                        throw new \RuntimeException('Akses pelanggan sedang diproses oleh proses lain.');
                    }

                    // Reload setelah lock: jangan terapkan snapshot Isolir jika
                    // pembayaran manual/callback sudah mengubah state pelanggan.
                    $data = Order::whereKey($snapshot->id)->first();
                    if (! $data || $data->status !== 'Isolir') {
                        $attempted--;

                        continue;
                    }

                    $router = Router::find($data->id_router);
                    if (! $router) {
                        Log::warning("Retry isolir ditunda, router tidak ditemukan ({$data->idpel}, router {$data->id_router})");
                        throw new \RuntimeException('Router pelanggan tidak ditemukan.');
                    }

                    $ros = $this->makeRouteros();
                    if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
                        Log::warning("Retry isolir ditunda, Mikrotik tidak terhubung ({$data->idpel}, router {$router->id})");
                        throw new \RuntimeException('Mikrotik tidak terhubung.');
                    }

                    // Pembayaran sudah memperpanjang expdate tetapi RouterOS sempat
                    // gagal dibuka. Status Isolir dipertahankan sebagai antrean retry.
                    $restoreAccess = strtotime((string) $data->expdate) >= strtotime(date('Y-m-d'));
                    $profile = 'isolir';
                    if ($restoreAccess) {
                        $profile = (string) (Service::where('paket', $data->paket)->value('ppp_profile') ?? '');
                        if ($profile === '') {
                            throw new \RuntimeException('Profil paket untuk buka isolir tidak ditemukan.');
                        }
                    }

                    $username = trim((string) $data->pppoe_user);
                    if ($username === '') {
                        throw new \RuntimeException('Username RouterOS pelanggan kosong.');
                    }

                    $accountFound = match ($data->mode) {
                        'pppoe' => $this->applyPppoeProfile($ros, $username, $profile, $restoreAccess),
                        'hotspot' => $this->applyHotspotProfile($ros, $username, $profile, $restoreAccess),
                        default => throw new \RuntimeException("Mode pelanggan tidak didukung: {$data->mode}"),
                    };

                    if (! $accountFound && $restoreAccess) {
                        throw new \RuntimeException($data->mode === 'pppoe'
                            ? "PPPoE secret '{$username}' tidak ditemukan."
                            : "User hotspot '{$username}' tidak ditemukan.");
                    }

                    if ($restoreAccess) {
                        Order::whereKey($data->id)
                            ->where('status', 'Isolir')
                            ->where('expdate', $data->expdate)
                            ->update(['status' => 'Active']);
                    }

                    $succeeded++;
                } catch (\Throwable $e) {
                    $failed++;
                    $routerId = $router?->id ?? $snapshot->id_router;
                    if (! in_array($e->getMessage(), ['Router pelanggan tidak ditemukan.', 'Mikrotik tidak terhubung.'], true)) {
                        Log::warning("Retry isolir gagal ({$snapshot->idpel}, router {$routerId}): {$e->getMessage()}");
                    }
                } finally {
                    $ros?->disconnect();
                    if ($lockAcquired) {
                        try {
                            Invoice::releaseNamedLock($accessLock);
                        } catch (\Throwable $e) {
                            Log::warning("Gagal melepas customer access lock cron ({$snapshot->idpel}): {$e->getMessage()}");
                        }
                    }
                }
            }
        });

        echo "Isolir: {$attempted} dicoba, {$succeeded} berhasil, {$failed} akan dicoba lagi pada jadwal berikutnya<br/>";
    }

    private function applyPppoeProfile(RouterosAPI $ros, string $username, string $profile, bool $accountRequired): bool
    {
        $secrets = $ros->comm('/ppp/secret/getall', [
            '.proplist' => '.id,profile',
            '?name' => $username,
        ]);
        $this->ensureRouterCommandSucceeded($secrets, 'Lookup PPPoE secret');
        $secret = $this->findExactRouterItem($secrets, 'PPPoE secret');

        if (! $secret && $accountRequired) {
            return false;
        }

        if ($secret) {
            if (($secret['profile'] ?? null) !== $profile) {
                $this->ensureRouterCommandSucceeded($ros->comm('/ppp/secret/set', [
                    '.id' => $secret['.id'],
                    'profile' => $profile,
                ]), 'Ubah profil PPPoE');
            }
        }

        $active = $ros->comm('/ppp/active/getall', [
            '.proplist' => '.id',
            '?name' => $username,
        ]);
        $this->ensureRouterCommandSucceeded($active, 'Lookup sesi aktif PPPoE');
        $this->removeActiveSessions($ros, '/ppp/active/remove', $active, 'Hapus sesi aktif PPPoE');

        return $secret !== null;
    }

    private function applyHotspotProfile(RouterosAPI $ros, string $username, string $profile, bool $accountRequired): bool
    {
        $users = $ros->comm('/ip/hotspot/user/print', [
            '.proplist' => '.id,profile',
            '?name' => $username,
        ]);
        $this->ensureRouterCommandSucceeded($users, 'Lookup user hotspot');
        $user = $this->findExactRouterItem($users, 'User hotspot');

        if (! $user && $accountRequired) {
            return false;
        }

        if ($user) {
            if (($user['profile'] ?? null) !== $profile) {
                $this->ensureRouterCommandSucceeded($ros->comm('/ip/hotspot/user/set', [
                    '.id' => $user['.id'],
                    'profile' => $profile,
                ]), 'Ubah profil hotspot');
            }
        }

        $active = $ros->comm('/ip/hotspot/active/print', [
            '.proplist' => '.id',
            '?user' => $username,
        ]);
        $this->ensureRouterCommandSucceeded($active, 'Lookup sesi aktif hotspot');
        $this->removeActiveSessions($ros, '/ip/hotspot/active/remove', $active, 'Hapus sesi aktif hotspot');

        return $user !== null;
    }

    private function findExactRouterItem(mixed $response, string $label): ?array
    {
        if (! is_array($response)) {
            throw new \RuntimeException("Respons lookup {$label} tidak valid.");
        }

        $items = array_values(array_filter($response, fn ($item) => is_array($item) && ! empty($item['.id'])));
        if (count($items) > 1) {
            throw new \RuntimeException("{$label} ditemukan lebih dari satu.");
        }

        return $items[0] ?? null;
    }

    private function removeActiveSessions(RouterosAPI $ros, string $command, mixed $sessions, string $context): void
    {
        foreach (is_array($sessions) ? $sessions : [] as $session) {
            if (empty($session['.id'])) {
                continue;
            }

            $response = $ros->comm($command, ['.id' => $session['.id']]);
            if ($this->isNoSuchItemResponse($response)) {
                continue;
            }

            $this->ensureRouterCommandSucceeded($response, $context);
        }
    }

    private function isNoSuchItemResponse(mixed $response): bool
    {
        if (! is_array($response) || (! isset($response['!trap']) && ! isset($response['!fatal']))) {
            return false;
        }

        $message = (string) ($response['!trap'][0]['message'] ?? $response['!fatal'][0]['message'] ?? '');

        return strcasecmp(trim($message), 'no such item') === 0;
    }

    private function ensureRouterCommandSucceeded(mixed $response, ?string $context = null): void
    {
        if (is_array($response) && (isset($response['!trap']) || isset($response['!fatal']))) {
            $message = $response['!trap'][0]['message'] ?? $response['!fatal'][0]['message'] ?? 'Perintah RouterOS gagal.';

            throw new \RuntimeException($context ? "{$context}: {$message}" : $message);
        }
    }

    protected function makeRouteros(): RouterosAPI
    {
        return new RouterosAPI;
    }

    public function cetakinv()
    {
        foreach (Order::where('status', 'Active')->get() as $value) {
            $notification = Notification::first();
            $service = Service::where('paket', $value->paket)->first();
            if (! $notification || ! $service) {
                continue;
            }
            $lama = (new DateTime)->diff(new DateTime($value->expdate));
            if ($lama->days < $notification->sebelum && Invoice::where(['idpel' => $value->idpel, 'status' => 'Unpaid'])->count() == 0) {
                $kodebaru = $this->generateInvoiceCode().randinv(5);
                $price = $service->harga + $service->harga * $service->ppn / 100;
                if ($notification->notif_tagihan == 'on') {
                    $template = TemplateMessage::all()->last();
                    $message = str_replace(['{id_pelanggan}', '{expdate}', '{link_web}', '{nomor_invoice}', '{link_bayar}', '{link_invoice}'], [$value->idpel, tanggal_indo(date('Y-m-d', strtotime($value->expdate))), url('/'), $kodebaru, url('tagihan/'.$kodebaru), url('invoice/'.$kodebaru)], $template?->notif_tagihan ?? '');
                    WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_TAGIHAN, $value->nomor, $message, [$value->nama, $value->idpel, tanggal_indo(date('Y-m-d', strtotime($value->expdate))), url('tagihan/'.$kodebaru), $kodebaru, $value->paket, url('invoice/'.$kodebaru)], $kodebaru);
                }
                Invoice::insert(['code' => $kodebaru, 'idpel' => $value->idpel, 'nama' => $value->nama, 'package' => $value->paket, 'price' => $price, 'status' => 'Unpaid', 'date' => date('Y-m-d'), 'expdate' => $value->expdate, 'account' => 'user']);
                echo 'berhasil generate invoice<br/>';
            }
        }
    }

    public function reminderInvoice()
    {
        $notification = Notification::first();
        $template = TemplateMessage::all()->last();
        foreach (Invoice::where('status', 'Unpaid')->where('account', 'user')->get() as $value) {
            $order = Order::where('nama', $value->nama)->first();
            if (! $notification || ! $template || ! $order) {
                continue;
            }
            $date = date('Y-m-d');
            $expdateFormatted = date('Y-m-d', strtotime($value->expdate));
            $dayDifference = floor((strtotime($expdateFormatted) - strtotime($date)) / (60 * 60 * 24));
            $send = (($notification->notif_jatuh_tempo_h == 'on' && $expdateFormatted == $date) || ($dayDifference == 1 && $notification->notif_jatuh_tempo_h1 == 'on') || ($dayDifference == 3 && $notification->notif_jatuh_tempo_h3 == 'on') || ($dayDifference == 7 && $notification->notif_jatuh_tempo_h7 == 'on'));
            if ($send) {
                $message = str_replace(['{expdate}', '{nomor_invoice}', '{link_web}', '{link_bayar}', '{link_invoice}'], [tanggal_indo($expdateFormatted), $value->code, url('/'), url('tagihan/'.$value->code), url('invoice/'.$value->code)], $template->notif_pengingat);
                WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_PENGINGAT, $order->nomor, $message, [tanggal_indo($expdateFormatted), $value->code, url('tagihan/'.$value->code), $order->paket, url('invoice/'.$value->code)], $value->code);
                echo 'berhasil mengirimkan reminder<br/>';
            }
        }
    }

    // Endpoint cron untuk panel hosting yang hanya bisa hit URL (bukan crontab).
    // Token stabil per-install (turunan APP_KEY) — tanpa perubahan skema DB.
    public function cron($key)
    {
        if (! hash_equals(self::cronToken(), (string) $key)) {
            abort(403, 'Invalid cron token');
        }

        Artisan::call('schedule:run');

        return response('OK '.now()->toDateTimeString()."\n".Artisan::output());
    }

    // Token cron per-install, dipakai route auto/cron/{key} & ditampilkan di UI.
    public static function cronToken(): string
    {
        return substr(hash('sha256', config('app.key').'|landaknet-cron'), 0, 40);
    }

    // Alias agar scheduler bisa memanggil task 'reminder' (nilai cron_setting.task)
    // sementara logika sebenarnya ada di reminderInvoice().
    public function reminder()
    {
        return $this->reminderInvoice();
    }

    private function generateInvoiceCode()
    {
        $kode = Invoice::selectRaw('MAX(code) AS kodex')->first()->kodex;
        $nourut = (int) substr($kode, 3, 4);
        $nourut++;

        return 'INV'.sprintf('%04s', $nourut);
    }
}
