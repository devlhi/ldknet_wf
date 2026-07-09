<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Http;

class WhatsAppMetaApi
{
    protected string $apiUrl;

    protected string $apiKey;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = rtrim((string) ($apiUrl ?: 'https://graph.facebook.com/v20.0'), '/');
        $this->apiKey = (string) $apiKey;
    }

    public function sendMessage($sender, $number, $message)
    {
        return $this->postMessage($sender, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeNumber($number),
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => (string) $message,
            ],
        ]);
    }

    public function sendMessageMedia($sender, $number, $mediatype, $caption, $url)
    {
        $type = $this->normalizeMediaType($mediatype);

        $media = ['link' => $url];
        // Graph API menolak field caption untuk audio/sticker
        if (! in_array($type, ['audio', 'sticker']) && (string) $caption !== '') {
            $media['caption'] = (string) $caption;
        }

        return $this->postMessage($sender, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeNumber($number),
            'type' => $type,
            $type => $media,
        ]);
    }

    public function sendTemplate($sender, $number, $templateName, array $parameters = [], string $language = 'id')
    {
        $components = [];
        if ($parameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn ($value) => [
                    'type' => 'text',
                    'text' => (string) $value,
                ], $parameters),
            ];
        }

        return $this->postMessage($sender, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeNumber($number),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);
    }

    public function templates($businessAccountId)
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get($this->apiUrl.'/'.trim((string) $businessAccountId).'/message_templates');

        return $response->json();
    }

    public function createTemplate($businessAccountId, string $name, string $language, string $category, array|string $bodyTexts, ?string $headerText = null, string $footerText = 'Salam Hangat - ANNORTY NET'): array
    {
        $components = [];

        if ($headerText) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $headerText,
            ];
        }

        if ($bodyTexts !== []) {
            $components[] = [
                'type' => 'BODY',
                'text' => is_array($bodyTexts) ? implode("\n\n", $bodyTexts) : (string) $bodyTexts,
            ];
        }

        $components[] = [
            'type' => 'FOOTER',
            'text' => $footerText,
        ];

        $payload = [
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => $components,
        ];

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->post($this->apiUrl.'/'.trim((string) $businessAccountId).'/message_templates', $payload);

        return $response->json();
    }

    public function createTemplateFromDefinition($businessAccountId, array $def, ?string $language = null): array
    {
        return $this->createTemplate(
            $businessAccountId,
            $def['name'],
            $language ?: ($def['language'] ?? 'id'),
            $def['category'] ?? 'UTILITY',
            $def['body'] ?? '',
            $def['header'] ?? null,
            $def['footer'] ?? 'Salam Hangat - ANNORTY NET'
        );
    }

    public static function defaultTemplateDefinitions(): array
    {
        return [
            [
                'name' => 'notif_tagihan',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Tagihan Internet',
                // Urutan parameter WAJIB sama dengan pemanggil sendNotification(EVENT_TAGIHAN):
                // FinanceController::generateInvoice & AutoController::cetakinv kirim [nama, idpel, jatuh_tempo, link, no_invoice, paket]
                'body' => "Tagihan Internet Anda Telah Terbit\n\nNama: {{1}}\nID Pelanggan: {{2}}\nJatuh Tempo: {{3}}\nLink: {{4}}\nNo Invoice: {{5}}\nPaket: {{6}}\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['nama', 'id_pelanggan', 'jatuh_tempo', 'link', 'no_invoice', 'paket'],
            ],
            [
                'name' => 'notif_pengingat',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Pengingat Tagihan',
                // Urutan parameter untuk pemanggil sendNotification(EVENT_PENGINGAT): [jatuh_tempo, no_invoice, link, paket]
                'body' => "Pengingat Tagihan\n\nJatuh Tempo: {{1}}\nNo Invoice: {{2}}\nLink: {{3}}\nPaket: {{4}}\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['jatuh_tempo', 'no_invoice', 'link', 'paket'],
            ],
            [
                'name' => 'notif_tagihan_terbayar',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Pembayaran Diterima',
                // Urutan parameter untuk pemanggil sendNotification(EVENT_TERBAYAR): [id_pelanggan, no_invoice, link, paket]
                'body' => "Pembayaran Diterima\n\nID Pelanggan: {{1}}\nNo Invoice: {{2}}\nLink: {{3}}\nPaket: {{4}}\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['id_pelanggan', 'no_invoice', 'link', 'paket'],
            ],
            [
                'name' => 'notif_daftar_berhasil',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Pendaftaran Berhasil',
                // Password sengaja tidak disertakan: Meta menolak kredensial di template UTILITY. Password tetap dikirim via email.
                'body' => "Pendaftaran Anda Berhasil\n\nNama: {{1}}\nEmail: {{2}}\nID Pelanggan: {{3}}\nExpdate: {{4}}\nPaket: {{5}}\nLink: {{6}}\n\nInformasi akun Anda telah dikirim ke email terdaftar.\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['nama', 'email', 'id_pelanggan', 'expdate', 'paket', 'link'],
            ],
            [
                'name' => 'notif_isolir',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Layanan Dinonaktifkan',
                // Urutan parameter untuk pemanggil sendNotification(EVENT_ISOLIR): [nama, id_pelanggan, jatuh_tempo, link, paket]
                'body' => "Layanan Internet Anda Dinonaktifkan Sementara\n\nNama: {{1}}\nID Pelanggan: {{2}}\nJatuh Tempo: {{3}}\nMohon segera lakukan pembayaran untuk mengaktifkan kembali layanan.\nLink: {{4}}\nPaket: {{5}}\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['nama', 'id_pelanggan', 'jatuh_tempo', 'link', 'paket'],
            ],
            [
                'name' => 'notif_buka_isolir',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Layanan Aktif Kembali',
                // Urutan parameter untuk pemanggil sendNotification(EVENT_BUKA_ISOLIR): [nama, id_pelanggan, paket, link]
                'body' => "Layanan Internet Anda Telah Aktif Kembali\n\nNama: {{1}}\nID Pelanggan: {{2}}\nPaket: {{3}}\nTerima kasih atas pembayaran Anda.\nLink: {{4}}\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['nama', 'id_pelanggan', 'paket', 'link'],
            ],
        ];
    }

    protected function postMessage($sender, array $payload)
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->post($this->apiUrl.'/'.trim((string) $sender).'/messages', $payload);

        return $response->body();
    }

    protected function normalizeNumber($number): string
    {
        $number = preg_replace('/\D+/', '', (string) $number);

        if (str_starts_with($number, '0')) {
            return '62'.substr($number, 1);
        }

        return $number;
    }

    protected function normalizeMediaType($type): string
    {
        return match (strtolower((string) $type)) {
            'image', 'video', 'audio', 'document' => strtolower((string) $type),
            default => 'image',
        };
    }
}
