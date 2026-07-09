<?php

namespace App\Support;

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

        return WhatsAppGatewayResolver::make($gateway)->sendMessage(WhatsAppGatewayResolver::sender($gateway), $number, $message);
    }

    public static function sendMedia(string $number, string $type, string $caption, string $url): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        return WhatsAppGatewayResolver::make($gateway)->sendMessageMedia(WhatsAppGatewayResolver::sender($gateway), $number, $type, $caption, $url);
    }

    public static function sendNotification(string $event, string $number, string $message, array $parameters = []): mixed
    {
        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway) {
            return null;
        }

        $api = WhatsAppGatewayResolver::make($gateway);
        if (WhatsAppGatewayResolver::isMeta($gateway)) {
            return $api->sendTemplate(
                WhatsAppGatewayResolver::sender($gateway),
                $number,
                self::templateName($event),
                array_values($parameters),
                self::templateLanguage($event)
            );
        }

        return $api->sendMessage(WhatsAppGatewayResolver::sender($gateway), $number, $message);
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
