# 2026-07-08 01:25 WIB — codebuddy — WhatsApp Template + Switch Fix

## Konteks

User meminta memastikan template reminder/tagihan/terbayar/register tetap ada dan berfungsi, serta aplikasi hanya memakai salah satu gateway WhatsApp: gateway lama atau Meta Official.

## Perubahan Utama

- `app/Support/WhatsAppGatewayResolver.php`
  - `active()` sekarang filter `type='blast'` dan `mode='on'`, bukan row terakhir.
  - `makeFromActive()` return type diperbaiki menjadi `WhatsAppApi|WhatsAppMetaApi|null`.
- `app/Support/WhatsAppNotifier.php` dibuat.
  - `sendText()` dan `sendMedia()` memakai gateway aktif.
  - `sendNotification()` memakai `sendTemplate()` jika gateway aktif adalah Meta, dan `sendMessage()` jika gateway lama.
  - Event template: `tagihan`, `pengingat`, `terbayar`, `pelanggan_baru`.
- `config/services.php` dan `.env.example`
  - Tambah mapping env nama template Meta:
    - `WHATSAPP_META_TEMPLATE_TAGIHAN`
    - `WHATSAPP_META_TEMPLATE_PENGINGAT`
    - `WHATSAPP_META_TEMPLATE_TERBAYAR`
    - `WHATSAPP_META_TEMPLATE_PELANGGAN_BARU`
    - language default/env per template.

## Flow yang Diubah

- `AutoController`
  - Cetak invoice otomatis `notif_tagihan` memakai `WhatsAppNotifier::EVENT_TAGIHAN`.
  - Reminder jatuh tempo `notif_pengingat` memakai `EVENT_PENGINGAT`.
- `Admin/FinanceController`
  - Generate invoice manual `notif_tagihan` memakai `EVENT_TAGIHAN`.
  - Invoice terbayar `notif_tagihan_terbayar` memakai `EVENT_TERBAYAR`.
- `Admin/CustomerController`
  - Pelanggan baru `notif_pelanggan_baru` memakai `EVENT_PELANGGAN_BARU`.
- `WebhookController`
  - Flow tambah customer dari bot lama juga memakai `EVENT_PELANGGAN_BARU`.
- `Admin/BroadcastController`
  - Broadcast text/media memakai gateway aktif.
- `Admin/GatewayController`
  - Test send biasa memakai gateway aktif.
  - Meta templates memakai gateway aktif dan WABA ID eksplisit, tidak lagi default ke Phone Number ID.
  - `whatsappAdd()` diperbaiki agar membuat row `whatsapp_setting` valid tanpa kolom non-existent `whatsapp_number`.
- `resources/views/admin/gateway/whatsapp/meta-templates.blade.php`
  - Menampilkan mapping nama template Meta untuk tagihan, reminder, terbayar, pelanggan baru.

## Catatan Template Meta

Nama template default:
- `notif_tagihan`
- `notif_pengingat`
- `notif_tagihan_terbayar`
- `notif_pelanggan_baru`

Jika nama template approved di Meta berbeda, ubah env mapping. Urutan parameter yang dikirim:
- Tagihan: `nama_customer`, `id_pelanggan`, `expdate`, `link_web`, `nomor_invoice`
- Pengingat: `expdate`, `nomor_invoice`, `link_web`
- Terbayar: `id_pelanggan`, `nomor_invoice`, `link_web`
- Pelanggan baru: `nama_customer`, `email`, `id_pelanggan`, `expdate`, `paket`, `link_web`, `password`

## Verifikasi

- `php -l` file terkait: OK.
- `php artisan route:list`: 262 routes, route WA Meta tetap ada.
- `./vendor/bin/pint --test`: PASS 140 files.
- `composer test`: PASS 2/2.

## Follow-up

- Live test dengan token Meta asli dan WABA ID.
- Pastikan template Meta approved memakai urutan parameter di atas.
- Jika Meta template punya parameter berbeda, sesuaikan env/nama template atau mapping di `WhatsAppNotifier`.
