# 2026-07-08 01:40 WIB — codebuddy — WA Meta Settings di Menu Gateway

## Konteks

User meminta WhatsApp Meta Official ditambahkan di menu WhatsApp Gateway dan tidak perlu setting template/verify token lewat `.env`.

## Perubahan

- `app/Support/WhatsAppGatewayResolver.php`
  - Meta settings sekarang dibaca dari row `whatsapp_setting` aktif, bukan env.
  - Format compact disimpan di kolom `api_url` untuk provider Meta: `meta###graph_url|verify_token|waba_id|language|tagihan|pengingat|terbayar|pelanggan_baru` (URL-encoded per part).
  - Tidak mengubah skema DB.
  - `make()` memakai `metaGraphUrl()` agar Graph API tetap dapat URL asli walau `api_url` berisi compact settings.
  - `verifyToken()` memakai token dari gateway Meta aktif.
- `app/Support/WhatsAppNotifier.php`
  - Nama template dan language Meta sekarang dari gateway Meta aktif, bukan config/env.
- `app/Http/Controllers/Admin/GatewayController.php`
  - `whatsappAdd()` dan `whatsappUpdateNumber()` menyimpan Meta settings dari form admin.
  - `whatsappMetaTemplates()` memakai WABA ID dari gateway aktif dan Graph URL dari resolver.
- `resources/views/admin/gateway/whatsapp/edit.blade.php`
  - Tambah section “Pengaturan WhatsApp Meta Official”: Graph URL, WABA ID, Verify Token, Language, template tagihan/reminder/terbayar/pelanggan baru.
  - Webhook verify token yang tampil berasal dari gateway setting.
- `resources/views/admin/gateway/whatsapp/setup.blade.php`
  - Tambah field provider + Meta setting dasar saat create gateway.
- `resources/views/admin/gateway/whatsapp/meta-templates.blade.php`
  - Mapping template ditampilkan dari setting gateway, bukan `.env`.

## Catatan

- `.env` masih boleh ada sebagai fallback default lama, tetapi flow utama sekarang dari menu admin WhatsApp Gateway.
- Karena skema DB tidak boleh diubah, field Meta disimpan compact di `whatsapp_setting.api_url`. UI tetap menampilkan Graph URL asli, bukan payload compact.

## Verifikasi

- `php -l` resolver/controller/views terkait: OK.
- `./vendor/bin/pint`: 1 style issue fixed.
- `composer test`: PASS 2/2.
- `php artisan route:list`: 262 routes, route WA Meta tetap ada.

## Follow-up

- Live save dari menu admin WhatsApp Gateway dengan kredensial Meta asli.
- Pastikan panjang string compact masih cukup untuk kolom `api_url` 255, gunakan nama template singkat seperti default jika memungkinkan.
