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

    public function sendTemplate($sender, $number, $templateName, array $parameters = [], string $language = 'id', ?string $urlButtonParam = null)
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

        // Tombol URL dinamis (mis. "Bayar Sekarang" -> .../tagihan/{{1}}). Nilai
        // yang dikirim = suffix URL (kode invoice). Hanya dipakai bila template
        // memang punya tombol URL, kalau tidak Meta menolak.
        if ($urlButtonParam !== null && $urlButtonParam !== '') {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => (string) $urlButtonParam],
                ],
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

    public function createTemplate($businessAccountId, string $name, string $language, string $category, array|string $bodyTexts, ?string $headerText = null, string $footerText = 'Salam Hangat - ANNORTY NET', array $bodyExample = [], array $button = []): array
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
            $body = [
                'type' => 'BODY',
                'text' => is_array($bodyTexts) ? implode("\n\n", $bodyTexts) : (string) $bodyTexts,
            ];
            // Meta mewajibkan contoh nilai untuk body yang punya variabel {{n}},
            // kalau tidak template ditolak saat pembuatan via API.
            if ($bodyExample !== []) {
                $body['example'] = ['body_text' => [array_values($bodyExample)]];
            }
            $components[] = $body;
        }

        $components[] = [
            'type' => 'FOOTER',
            'text' => $footerText,
        ];

        // Tombol URL dinamis: base URL dibuat dari domain situs SAAT INI + '/{{1}}'.
        // Suffix {{1}} diisi kode invoice saat kirim. Base ikut domain aktif; kalau
        // domain berganti, buat ulang template agar base tombol ikut baru.
        if ($button !== []) {
            $base = rtrim(url($button['path'] ?? 'tagihan'), '/');
            $urlButton = [
                'type' => 'URL',
                'text' => $button['text'] ?? 'Bayar Sekarang',
            ];
            if (($button['dynamic'] ?? true) === false) {
                // Tombol statis: URL tetap (mis. halaman cek tagihan), tanpa variabel
                // sehingga tidak perlu parameter saat kirim (aman bila kode tak ada).
                $urlButton['url'] = $base;
            } else {
                // Tombol dinamis: URL diakhiri {{1}} yang diisi kode invoice saat kirim.
                $urlButton['url'] = $base.'/{{1}}';
                $urlButton['example'] = [$base.'/INV0001'];
            }
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => [$urlButton],
            ];
        }

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
            $def['footer'] ?? 'Salam Hangat - ANNORTY NET',
            $def['example'] ?? [],
            $def['button'] ?? []
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
                // FinanceController::generateInvoice & AutoController::cetakinv kirim
                // [nama, idpel, jatuh_tempo, link(=URL bayar tagihan/{code}), no_invoice, paket, link_invoice].
                // Param ke-7 (link_invoice) otomatis dipotong utk template 6-variabel ini
                // (WhatsAppNotifier::templateParamCount). Variabel 'link' TETAP bernama link,
                // isinya kini link pembayaran, bukan beranda situs.
                'body' => "Tagihan Internet Anda Telah Terbit\n\nNama: {{1}}\nID Pelanggan: {{2}}\nJatuh Tempo: {{3}}\nLink: {{4}}\nNo Invoice: {{5}}\nPaket: {{6}}\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['nama', 'id_pelanggan', 'jatuh_tempo', 'link', 'no_invoice', 'paket'],
            ],
            [
                'name' => 'notif_pengingat',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Pengingat Tagihan',
                // Urutan parameter pemanggil sendNotification(EVENT_PENGINGAT):
                // [jatuh_tempo, no_invoice, link(=URL bayar tagihan/{code}), paket, link_invoice].
                // Param ke-5 dipotong otomatis utk template 4-variabel ini.
                'body' => "Pengingat Tagihan\n\nJatuh Tempo: {{1}}\nNo Invoice: {{2}}\nLink: {{3}}\nPaket: {{4}}\n\nSalam Hangat\n\nANNORTY NET",
                'footer' => 'Salam Hangat - ANNORTY NET',
                'parameters' => ['jatuh_tempo', 'no_invoice', 'link', 'paket'],
            ],
            [
                'name' => 'notif_tagihan_terbayar',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Pembayaran Diterima',
                // Urutan parameter pemanggil sendNotification(EVENT_TERBAYAR):
                // [id_pelanggan, no_invoice, link(=URL kwitansi invoice/{code}), paket].
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
                // Urutan parameter pemanggil sendNotification(EVENT_ISOLIR):
                // [nama, id_pelanggan, jatuh_tempo, link(=URL bayar tagihan Unpaid, fallback halaman cek tagihan), paket].
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

            // =================================================================
            // Template versi lengkap (_v2) — meniru "Template Message" lama yang
            // lebih ramah & lengkap. Sengaja pakai NAMA BARU supaya template lama
            // tetap APPROVED dan notifikasi tetap terkirim selama v2 menunggu
            // verifikasi Meta. Setelah v2 disetujui Meta, ganti pemetaan event ke
            // nama _v2 di menu WhatsApp Gateway. Urutan {{n}} WAJIB sama dgn versi
            // lama agar pemanggil sendNotification() tidak perlu diubah.
            // =================================================================
            [
                // Base nama sengaja beda dari "tagihan": nama "notif_tagihan_v2" pernah
                // dihapus dari Meta dan terkunci (tidak bisa dipakai ulang), tapi akhiran
                // _v2 tetap dipakai konsisten dengan template lain di bawah.
                'name' => 'notif_tagihanbaru_v2',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Tagihan Internet Anda',
                'body' => "Halo Bapak/Ibu {{1}} yang terhormat,\n\nID Pelanggan: {{2}}\nPaket: {{6}}\n\nTagihan Anda bulan ini sudah terbit dengan nomor invoice {{5}}.\nMasa aktif sampai tanggal {{3}}.\n\nSilakan lakukan pembayaran melalui link berikut:\n{{4}}\n\nDetail invoice: {{7}}\n\nTerima kasih.",
                'footer' => 'ANNORTY NET - pesan otomatis, mohon tidak dibalas',
                'parameters' => ['nama', 'id_pelanggan', 'jatuh_tempo', 'link_bayar', 'no_invoice', 'paket', 'link_invoice'],
                'example' => ['Budi Santoso', 'LN0001', '31 Juli 2026', 'https://annortynet.com/tagihan/INV0001', 'INV0001', 'Paket 20 Mbps', 'https://annortynet.com/invoice/INV0001'],
                'button' => ['text' => 'Bayar Sekarang', 'path' => 'tagihan'],
            ],
            [
                'name' => 'notif_pengingat_v2',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Pengingat Tagihan',
                'body' => "Kepada Bapak/Ibu pelanggan yang terhormat,\n\nIni adalah pengingat bahwa tagihan Anda bulan ini belum dibayarkan.\n\nNo Invoice: {{2}}\nPaket: {{4}}\nMasa aktif sampai tanggal {{1}}.\n\nSilakan lakukan pembayaran melalui link berikut:\n{{3}}\n\nDetail invoice: {{5}}\n\nTerima kasih.",
                'footer' => 'ANNORTY NET - pesan otomatis, mohon tidak dibalas',
                'parameters' => ['jatuh_tempo', 'no_invoice', 'link_bayar', 'paket', 'link_invoice'],
                'example' => ['31 Juli 2026', 'INV0001', 'https://annortynet.com/tagihan/INV0001', 'Paket 20 Mbps', 'https://annortynet.com/invoice/INV0001'],
                'button' => ['text' => 'Bayar Sekarang', 'path' => 'tagihan'],
            ],
            [
                'name' => 'notif_tagihan_terbayar_v2',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Pembayaran Diterima',
                'body' => "Halo Bapak/Ibu yang terhormat,\n\nID Pelanggan: {{1}}\nPaket: {{4}}\n\nTagihan Anda dengan nomor invoice {{2}} sudah kami terima dan LUNAS.\nTerima kasih atas pembayaran Anda.\n\nDetail invoice dapat dilihat di link berikut:\n{{3}}\n\nTerima kasih.",
                'footer' => 'ANNORTY NET - pesan otomatis, mohon tidak dibalas',
                'parameters' => ['id_pelanggan', 'no_invoice', 'link_invoice', 'paket'],
                'example' => ['LN0001', 'INV0001', 'https://annortynet.com/invoice/INV0001', 'Paket 20 Mbps'],
            ],
            [
                'name' => 'notif_daftar_berhasil_v2',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Selamat Bergabung',
                'body' => "Terima kasih {{1}}, Anda telah bergabung menjadi pelanggan baru kami.\n\nInformasi layanan Anda:\nEmail: {{2}}\nID Pelanggan: {{3}}\nPaket: {{5}}\nMasa aktif sampai tanggal {{4}}.\n\nInformasi akun telah dikirim ke email terdaftar. Tagihan akan kami infokan via WhatsApp sekitar H-5 sebelum jatuh tempo.\n\nPortal pelanggan: {{6}}\n\nTerima kasih.",
                'footer' => 'ANNORTY NET - pesan otomatis, mohon tidak dibalas',
                'parameters' => ['nama', 'email', 'id_pelanggan', 'expdate', 'paket', 'link_web'],
                'example' => ['Budi Santoso', 'budi@email.com', 'LN0001', '31 Juli 2026', 'Paket 20 Mbps', 'https://annortynet.com'],
            ],
            [
                'name' => 'notif_isolir_v2',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Layanan Dinonaktifkan Sementara',
                'body' => "Halo Bapak/Ibu {{1}} yang terhormat,\n\nID Pelanggan: {{2}}\nPaket: {{5}}\n\nMohon maaf, layanan internet Anda untuk sementara kami nonaktifkan karena tagihan yang jatuh tempo pada {{3}} belum dibayarkan.\n\nSilakan lakukan pembayaran melalui link berikut agar layanan aktif kembali:\n{{4}}\n\nTerima kasih.",
                'footer' => 'ANNORTY NET - pesan otomatis, mohon tidak dibalas',
                'parameters' => ['nama', 'id_pelanggan', 'jatuh_tempo', 'link_bayar', 'paket'],
                'example' => ['Budi Santoso', 'LN0001', '31 Juli 2026', 'https://annortynet.com', 'Paket 20 Mbps'],
                // Tombol DINAMIS ke tagihan pelanggan. Suffix {{1}} = kode invoice
                // Unpaid bila ada, kalau tidak = ID Pelanggan (selalu ada). Halaman
                // /tagihan/{ref} resolve keduanya, jadi tombol tidak pernah gagal kirim.
                'button' => ['text' => 'Bayar Sekarang', 'path' => 'tagihan'],
            ],
            [
                'name' => 'notif_buka_isolir_v2',
                'language' => 'id',
                'category' => 'UTILITY',
                'header' => 'Layanan Aktif Kembali',
                'body' => "Halo Bapak/Ibu {{1}} yang terhormat,\n\nID Pelanggan: {{2}}\nPaket: {{3}}\n\nKabar baik! Pembayaran Anda telah kami terima dan layanan internet Anda sudah AKTIF kembali.\nTerima kasih atas kepercayaan Anda.\n\nPortal pelanggan: {{4}}\n\nTerima kasih.",
                'footer' => 'ANNORTY NET - pesan otomatis, mohon tidak dibalas',
                'parameters' => ['nama', 'id_pelanggan', 'paket', 'link_web'],
                'example' => ['Budi Santoso', 'LN0001', 'Paket 20 Mbps', 'https://annortynet.com'],
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
