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
        foreach (Order::where('status', 'Isolir')->get() as $data) {
            $router = Router::find($data->id_router);
            if (! $router) {
                continue;
            }
            $ros = new RouterosAPI;
            if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
                continue;
            }
            if ($data->mode == 'pppoe') {
                $ros->comm('/ppp/secret/set', ['numbers' => $data->pppoe_user, 'profile' => 'isolir']);
                $active = $ros->comm('/ppp/active/getall', ['.proplist' => '.id', '?name' => $data->pppoe_user]);
                if (! empty($active)) {
                    foreach ($active as $act) {
                        $ros->comm('/ppp/active/remove', ['.id' => $act['.id']]);
                    }
                }
            } elseif ($data->mode == 'hotspot') {
                $ros->comm('/ip/hotspot/user/set', ['numbers' => $data->pppoe_user, 'profile' => 'isolir']);
                $active = $ros->comm('/ip/hotspot/active/print', ['?user' => $data->pppoe_user]);
                if (! empty($active)) {
                    foreach ($active as $act) {
                        $ros->comm('/ip/hotspot/active/remove', ['.id' => $act['.id']]);
                    }
                }
            }
        }
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
