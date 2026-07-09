<?php

namespace App\Support;

use App\Models\WaInboxMessage;
use App\Models\WhatsappSetting;
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

    public static function sendText(string $number, string $message): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        $response = WhatsAppGatewayResolver::make($gateway)->sendMessage(WhatsAppGatewayResolver::sender($gateway), $number, $message);
        self::logOutbound($gateway, $number, $message, 'text', $response);

        return $response;
    }

    public static function sendMedia(string $number, string $type, string $caption, string $url): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        $response = WhatsAppGatewayResolver::make($gateway)->sendMessageMedia(WhatsAppGatewayResolver::sender($gateway), $number, $type, $caption, $url);
        self::logOutbound($gateway, $number, '['.ucfirst($type).'] '.$caption."\n".$url, 'media', $response);

        return $response;
    }

    public static function sendNotification(string $event, string $number, string $message, array $parameters = []): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        $api = WhatsAppGatewayResolver::make($gateway);
        if (WhatsAppGatewayResolver::isMeta($gateway)) {
            $response = $api->sendTemplate(
                WhatsAppGatewayResolver::sender($gateway),
                $number,
                self::templateName($event),
                array_values($parameters),
                self::templateLanguage($event)
            );

            // Simpan isi pesan versi teks (bukan payload template) agar terbaca di inbox.
            self::logOutbound($gateway, $number, $message, 'template:'.self::templateName($event), $response);

            return $response;
        }

        return $api->sendMessage(WhatsAppGatewayResolver::sender($gateway), $number, $message);
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

        return str_starts_with($number, '0') ? '62'.substr($number, 1) : $number;
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
