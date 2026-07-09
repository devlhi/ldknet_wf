<?php

namespace App\Support;

use App\Libraries\WhatsAppApi;
use App\Libraries\WhatsAppMetaApi;
use App\Models\WhatsappSetting;

class WhatsAppGatewayResolver
{
    public const PROVIDER_WABLAS = 'wablas';

    public const PROVIDER_META = 'meta';

    public static function isMeta(WhatsappSetting $gateway): bool
    {
        if (str_contains(strtolower((string) $gateway->nama), 'meta')) {
            return true;
        }

        return str_contains((string) $gateway->api_url, 'graph.facebook.com') || str_starts_with((string) $gateway->api_url, 'meta###');
    }

    public static function providerLabel(WhatsappSetting $gateway): string
    {
        return self::isMeta($gateway) ? self::PROVIDER_META : self::PROVIDER_WABLAS;
    }

    public static function make(WhatsappSetting $gateway): WhatsAppApi|WhatsAppMetaApi
    {
        if (self::isMeta($gateway)) {
            return new WhatsAppMetaApi(self::metaGraphUrl($gateway), $gateway->api_key);
        }

        return new WhatsAppApi($gateway->api_url, $gateway->api_key);
    }

    /**
     * Nomor/ID pengirim yang dipakai library saat kirim pesan.
     * Meta: Phone Number ID (bisa >15 digit) disimpan di blob meta karena
     * kolom DB `sender` cuma varchar(15). Gateway lama: pakai kolom sender apa adanya.
     */
    public static function sender(WhatsappSetting $gateway): string
    {
        if (self::isMeta($gateway)) {
            $phoneNumberId = (string) (self::metaSettings($gateway)['phone_number_id'] ?? '');

            return $phoneNumberId !== '' ? $phoneNumberId : (string) $gateway->sender;
        }

        return (string) $gateway->sender;
    }

    public static function active(string $type = 'blast'): ?WhatsappSetting
    {
        return WhatsappSetting::where('type', $type)
            ->where('mode', 'on')
            ->orderByDesc('id')
            ->first();
    }

    public static function makeFromActive(string $type = 'blast'): WhatsAppApi|WhatsAppMetaApi|null
    {
        $gateway = self::active($type);

        return $gateway ? self::make($gateway) : null;
    }

    public static function verifyToken(): string
    {
        $gateway = self::active();
        if ($gateway && self::isMeta($gateway)) {
            $settings = self::metaSettings($gateway);

            return (string) ($settings['verify_token'] ?? '');
        }

        return (string) (config('services.whatsapp_meta.verify_token') ?? 'landaknet-meta-webhook');
    }

    public static function metaSettings(WhatsappSetting $gateway): array
    {
        $json = self::extractMetaJson($gateway);

        return array_replace_recursive(self::defaultMetaSettings(), $json);
    }

    public static function saveMetaSettings(WhatsappSetting $gateway, array $settings): void
    {
        $settings = array_merge(self::defaultMetaSettings(), $settings);
        $payload = [];
        foreach ($settings as $key => $value) {
            if (in_array($key, ['graph_url', 'verify_token', 'waba_id', 'phone_number_id', 'language', 'templates'])) {
                $payload[$key] = $value;
            }
        }

        $gateway->api_url = self::encodeMetaSettings($payload);
        $gateway->save();
    }

    public static function metaGraphUrl(WhatsappSetting $gateway): string
    {
        $settings = self::metaSettings($gateway);

        return (string) ($settings['graph_url'] ?? 'https://graph.facebook.com/v20.0');
    }

    public static function metaVerifyToken(WhatsappSetting $gateway): string
    {
        return (string) (self::metaSettings($gateway)['verify_token'] ?? '');
    }

    public static function metaWabaId(WhatsappSetting $gateway): string
    {
        return (string) (self::metaSettings($gateway)['waba_id'] ?? '');
    }

    public static function metaPhoneNumberId(WhatsappSetting $gateway): string
    {
        return (string) (self::metaSettings($gateway)['phone_number_id'] ?? '');
    }

    public static function defaultMetaSettings(): array
    {
        return [
            'graph_url' => 'https://graph.facebook.com/v20.0',
            'verify_token' => 'landaknet-meta-webhook',
            'waba_id' => '',
            'phone_number_id' => '',
            'language' => 'id',
            'templates' => [
                'tagihan' => 'notif_tagihan',
                'pengingat' => 'notif_pengingat',
                'terbayar' => 'notif_tagihan_terbayar',
                'pelanggan_baru' => 'notif_daftar_berhasil',
            ],
        ];
    }

    protected static function extractMetaJson(WhatsappSetting $gateway): array
    {
        $apiUrl = (string) $gateway->api_url;

        if (str_starts_with($apiUrl, 'meta###')) {
            return self::parseCompactMeta($apiUrl);
        }

        // Legacy: graph.facebook.com###base64(json)
        if (str_starts_with($apiUrl, 'graph.facebook.com###')) {
            $encoded = substr($apiUrl, strlen('graph.facebook.com###'));
            $decoded = base64_decode($encoded, true);
            if ($decoded === false) {
                return [];
            }

            $data = json_decode($decoded, true);

            return is_array($data) ? $data : [];
        }

        return [];
    }

    protected static function parseCompactMeta(string $apiUrl): array
    {
        $payload = substr($apiUrl, strlen('meta###'));
        $parts = array_map('rawurldecode', explode('|', $payload));
        $templates = [
            'tagihan' => $parts[4] ?? '',
            'pengingat' => $parts[5] ?? '',
            'terbayar' => $parts[6] ?? '',
            'pelanggan_baru' => $parts[7] ?? '',
        ];

        return [
            'graph_url' => $parts[0] ?? '',
            'verify_token' => $parts[1] ?? '',
            'waba_id' => $parts[2] ?? '',
            'language' => $parts[3] ?? 'id',
            'templates' => $templates,
            // Ditambahkan di indeks 8 supaya blob lama (tanpa field ini) tetap terbaca.
            'phone_number_id' => $parts[8] ?? '',
        ];
    }

    public static function encodeMetaSettings(array $settings): string
    {
        $templates = $settings['templates'] ?? [];

        $parts = [
            $settings['graph_url'] ?? 'https://graph.facebook.com/v20.0',
            $settings['verify_token'] ?? '',
            $settings['waba_id'] ?? '',
            $settings['language'] ?? 'id',
            $templates['tagihan'] ?? 'notif_tagihan',
            $templates['pengingat'] ?? 'notif_pengingat',
            $templates['terbayar'] ?? 'notif_tagihan_terbayar',
            $templates['pelanggan_baru'] ?? 'notif_daftar_berhasil',
            $settings['phone_number_id'] ?? '',
        ];

        return 'meta###'.implode('|', array_map('rawurlencode', $parts));
    }
}
