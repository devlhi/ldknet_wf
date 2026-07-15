<?php

namespace App\Support;

use App\Libraries\WhatsAppMetaApi;
use App\Models\WaInboxMessage;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppNotifier
{
    public const EVENT_TAGIHAN = 'tagihan';

    public const EVENT_PENGINGAT = 'pengingat';

    public const EVENT_TERBAYAR = 'terbayar';

    public const EVENT_PELANGGAN_BARU = 'pelanggan_baru';

    public const EVENT_ISOLIR = 'isolir';

    public const EVENT_BUKA_ISOLIR = 'buka_isolir';

    /**
     * Nama template Meta default per event. Dipakai untuk gateway lama (match)
     * maupun sebagai fallback Meta saat event belum di-mapping di setting gateway.
     */
    private const DEFAULT_TEMPLATE_NAMES = [
        self::EVENT_TAGIHAN => 'notif_tagihan',
        self::EVENT_PENGINGAT => 'notif_pengingat',
        self::EVENT_TERBAYAR => 'notif_tagihan_terbayar',
        self::EVENT_PELANGGAN_BARU => 'notif_daftar_berhasil',
        self::EVENT_ISOLIR => 'notif_isolir',
        self::EVENT_BUKA_ISOLIR => 'notif_buka_isolir',
    ];

    public static function sendText(string $number, string $message, bool $logOutbound = true): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        $response = WhatsAppGatewayResolver::make($gateway)->sendMessage(WhatsAppGatewayResolver::sender($gateway), $number, $message);
        if ($logOutbound) {
            self::logOutbound($gateway, $number, $message, 'text', $response);
        }

        return $response;
    }

    public static function sendMedia(string $number, string $type, string $caption, string $url, bool $logOutbound = true): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        $response = WhatsAppGatewayResolver::make($gateway)->sendMessageMedia(WhatsAppGatewayResolver::sender($gateway), $number, $type, $caption, $url);
        if ($logOutbound) {
            self::logOutbound($gateway, $number, '['.ucfirst($type).'] '.$caption."\n".$url, 'media', $response);
        }

        return $response;
    }

    public static function sendNotification(string $event, string $number, string $message, array $parameters = [], ?string $buttonSuffix = null): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        $api = WhatsAppGatewayResolver::make($gateway);
        if (WhatsAppGatewayResolver::isMeta($gateway)) {
            $templateName = self::templateName($event);

            // Pemanggil bisa mengirim lebih banyak parameter daripada jumlah variabel
            // template (mis. tagihan_v2 menambah link_invoice, tapi template lama masih
            // 6 variabel). Potong sesuai jumlah variabel template supaya Meta tidak
            // menolak (jumlah harus sama persis). Variabel tambahan diletakkan di urutan
            // TERAKHIR agar pemotongan aman untuk template lama.
            $params = array_values($parameters);
            $expected = self::templateParamCount($templateName);
            if ($expected !== null && count($params) > $expected) {
                $params = array_slice($params, 0, $expected);
            }

            // Suffix tombol URL (kode invoice) hanya dikirim bila template yang
            // dipetakan memang punya tombol (mis. _v2). Template lama tanpa tombol
            // -> null, supaya Meta tidak menolak pesan.
            $urlButtonParam = ($buttonSuffix !== null && $buttonSuffix !== '' && self::templateHasDynamicButton($templateName))
                ? $buttonSuffix
                : null;

            $response = $api->sendTemplate(
                WhatsAppGatewayResolver::sender($gateway),
                $number,
                $templateName,
                $params,
                self::templateLanguage($event),
                $urlButtonParam
            );

            // Catat ke inbox dengan teks template Meta asli (parameter sudah terisi)
            // agar tampilan inbox = pesan yang benar-benar diterima pelanggan.
            $inboxBody = self::renderTemplateBody($gateway, $templateName, $params) ?? $message;
            self::logOutbound($gateway, $number, $inboxBody, 'template:'.$templateName, $response);

            return $response;
        }

        return $api->sendMessage(WhatsAppGatewayResolver::sender($gateway), $number, $message);
    }

    /**
     * Render body template Meta dengan parameter terisi, untuk dicatat ke inbox.
     * Definisi template diambil dari Graph API dan di-cache 6 jam agar tidak
     * memanggil API tiap kirim. Gagal ambil (offline/token) -> null, pemanggil
     * fallback ke teks pesan lama.
     */
    private static function renderTemplateBody(WhatsappSetting $gateway, string $templateName, array $parameters): ?string
    {
        try {
            $body = self::templateBodyText($gateway, $templateName);
            if ($body === null) {
                return null;
            }

            foreach ($parameters as $i => $value) {
                $body = str_replace('{{'.($i + 1).'}}', (string) $value, $body);
            }

            return $body;
        } catch (\Throwable $e) {
            Log::warning("Gagal render template {$templateName} untuk inbox: {$e->getMessage()}");

            return null;
        }
    }

    private static function templateBodyText(WhatsappSetting $gateway, string $templateName): ?string
    {
        $cacheKey = 'wa_meta_tpl_body:'.$gateway->id.':'.$templateName;

        $body = Cache::remember($cacheKey, now()->addHours(6), function () use ($gateway, $templateName) {
            $wabaId = WhatsAppGatewayResolver::metaWabaId($gateway);
            if ($wabaId === '') {
                return '';
            }

            $result = WhatsAppGatewayResolver::make($gateway)->templates($wabaId);
            foreach ((array) data_get($result, 'data', []) as $tpl) {
                if (($tpl['name'] ?? '') !== $templateName) {
                    continue;
                }
                foreach ((array) ($tpl['components'] ?? []) as $component) {
                    if (strtoupper((string) ($component['type'] ?? '')) === 'BODY') {
                        return (string) ($component['text'] ?? '');
                    }
                }
            }

            // Template tidak ditemukan di Meta -> coba definisi bawaan aplikasi.
            foreach (WhatsAppMetaApi::defaultTemplateDefinitions() as $def) {
                if (($def['name'] ?? '') === $templateName) {
                    return (string) ($def['body'] ?? '');
                }
            }

            return '';
        });

        return $body !== '' ? $body : null;
    }

    /**
     * Catat pesan keluar Meta ke wa_inbox_messages agar riwayat notifikasi
     * (invoice, reminder, isolir, broadcast) terlihat di WhatsApp Inbox
     * beserta status terkirim/gagal dari respons Graph API.
     * Gateway non-Meta tidak dicatat (inbox khusus percakapan Meta).
     */
    private static function logOutbound(WhatsappSetting $gateway, string $number, string $body, string $type, mixed $response): void
    {
        if (! WhatsAppGatewayResolver::isMeta($gateway)) {
            return;
        }

        try {
            $result = is_string($response) ? json_decode($response, true) : (is_array($response) ? $response : null);
            $metaMessageId = data_get($result, 'messages.0.id');
            $error = data_get($result, 'error.message');

            WaInboxMessage::create([
                'from_number' => self::normalizeNumber($number),
                'direction' => 'out',
                'body' => $error ? $body."\n\n[GAGAL: ".Str::limit($error, 200).']' : $body,
                'message_type' => Str::limit($type, 30, ''),
                'meta_message_id' => $metaMessageId,
                'status' => $metaMessageId ? 'sent' : 'failed',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Logging tidak boleh menggagalkan pengiriman notifikasi.
            Log::warning('Gagal mencatat pesan WA keluar: '.$e->getMessage());
        }
    }

    /**
     * Samakan format nomor dengan pesan masuk webhook (62xxx tanpa simbol)
     * agar pesan keluar & masuk menyatu dalam satu thread percakapan.
     */
    private static function normalizeNumber(string $number): string
    {
        $number = preg_replace('/\D+/', '', $number);

        if (str_starts_with($number, '0')) {
            return '62'.substr($number, 1);
        }

        if ($number !== '' && ! str_starts_with($number, '62')) {
            return '62'.$number;
        }

        return $number;
    }

    /**
     * Apakah template punya tombol URL DINAMIS (butuh parameter suffix saat kirim).
     * Tombol statis tidak butuh parameter, jadi tidak dihitung di sini.
     */
    private static function templateHasDynamicButton(string $templateName): bool
    {
        foreach (WhatsAppMetaApi::defaultTemplateDefinitions() as $def) {
            if (($def['name'] ?? '') === $templateName) {
                return ! empty($def['button']) && ($def['button']['dynamic'] ?? true) !== false;
            }
        }

        return false;
    }

    /**
     * Jumlah variabel yang diharapkan template (dari definisi bawaan), untuk
     * memotong parameter berlebih. null = template tak dikenal (kirim apa adanya).
     */
    private static function templateParamCount(string $templateName): ?int
    {
        foreach (WhatsAppMetaApi::defaultTemplateDefinitions() as $def) {
            if (($def['name'] ?? '') === $templateName) {
                return count($def['parameters'] ?? []);
            }
        }

        return null;
    }

    public static function responseSucceeded(mixed $response, ?WhatsappSetting $gateway = null): bool
    {
        if ($response === null || $response === false || $response === '') {
            return false;
        }

        $gateway ??= WhatsAppGatewayResolver::active();
        $result = is_string($response) ? json_decode($response, true) : (is_array($response) ? $response : null);
        if (! is_array($result)) {
            return false;
        }

        if ($gateway && WhatsAppGatewayResolver::isMeta($gateway)) {
            return filled(data_get($result, 'messages.0.id')) && ! isset($result['error']);
        }

        $status = $result['status'] ?? null;
        $success = $result['success'] ?? null;
        $messageId = $result['message_id'] ?? $result['id'] ?? null;

        return ! isset($result['error'])
            && empty($result['errors'])
            && $success !== false
            && $status !== false
            && $status !== 'error'
            && $status !== 'failed'
            && (filled($messageId) || $status === true || $success === true || $status === 'success');
    }

    public static function responseError(mixed $response): string
    {
        $result = is_string($response) ? json_decode($response, true) : (is_array($response) ? $response : null);
        if (! is_array($result)) {
            return 'Gateway tidak memberikan respons yang valid.';
        }

        $error = data_get($result, 'error.message')
            ?? data_get($result, 'errors.message')
            ?? data_get($result, 'errors')
            ?? data_get($result, 'msg');

        return is_scalar($error) && trim((string) $error) !== ''
            ? Str::limit(trim((string) $error), 300)
            : 'Pesan ditolak oleh gateway WhatsApp.';
    }

    public static function templateName(string $event): string
    {
        $gateway = WhatsAppGatewayResolver::active();
        if ($gateway && WhatsAppGatewayResolver::isMeta($gateway)) {
            $templates = WhatsAppGatewayResolver::metaSettings($gateway)['templates'] ?? [];

            // Event yang belum dipetakan di setting gateway (mis. isolir/buka_isolir)
            // fallback ke nama template default, bukan ke nama event mentah.
            return (string) ($templates[$event] ?? self::DEFAULT_TEMPLATE_NAMES[$event] ?? $event);
        }

        return self::DEFAULT_TEMPLATE_NAMES[$event] ?? $event;
    }

    public static function templateLanguage(string $event): string
    {
        $gateway = WhatsAppGatewayResolver::active();
        if ($gateway && WhatsAppGatewayResolver::isMeta($gateway)) {
            return (string) (WhatsAppGatewayResolver::metaSettings($gateway)['language'] ?? 'id');
        }

        return 'id';
    }
}
