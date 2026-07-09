# 2026-07-08 01:10 WIB — codebuddy — WhatsApp Meta Official Integration

## Konteks

User meminta integrasi WhatsApp Meta Official lengkap: admin gateway setting, webhook receiver, dan template manager. Gateway harus dapat di-switch antara gateway lama (Wablas compatible) dan Meta Official. Tidak boleh mengubah skema DB.

## Keputusan

- **Penyimpanan provider tanpa skema baru**: discriminator disimpan di kolom `nama` (nilai `Meta Official`) dan/atau `api_url` (mengandung `graph.facebook.com`). Tidak ada kolom baru.
- **Switch gateway**: saat `whatsappUpdateNumber` menyimpan gateway dengan `mode=on`, semua gateway lain dengan `type` sama di-set `mode=off`.
- **Verify token webhook Meta**: dari config `services.whatsapp_meta.verify_token`, fallback env `WHATSAPP_META_VERIFY_TOKEN`, default `landaknet-meta-webhook`.
- **Bot Meta**: handler minimal `/help` dan `/cek` saja. Bot lama (Wablas/legacy) tetap dipertahankan untuk command admin lengkap sampai parity diimplementasikan.

## File Baru

- `app/Libraries/WhatsAppMetaApi.php` — Graph API client: `sendMessage`, `sendMessageMedia`, `sendTemplate`, `templates`.
- `app/Support/WhatsAppGatewayResolver.php` — deteksi provider + factory `make()`; `verifyToken()`.
- `config/services.php` — `whatsapp_meta.verify_token` + `graph_url`.
- `resources/views/admin/gateway/whatsapp/meta-templates.blade.php` — daftar template Meta + form test send.

## File Diubah

- `app/Http/Controllers/Admin/GatewayController.php` — `whatsappUpdateNumber` (provider switch + nama), `whatsappMetaTemplates`, `whatsappMetaTest`.
- `app/Http/Controllers/Admin/BroadcastController.php` — broadcast text/media pakai resolver.
- `app/Http/Controllers/Admin/CustomerController.php` — send WA pakai resolver.
- `app/Http/Controllers/Admin/FinanceController.php` — send WA pakai resolver.
- `app/Http/Controllers/AutoController.php` — cron WA pakai resolver.
- `app/Http/Controllers/WebhookController.php` — reply WA pakai resolver + `whatsappMetaVerify` + `whatsappMeta` + `handleMetaTextCommand`.
- `routes/modules/gateway.php` — `GET admin/whatsapp/meta/templates`, `POST admin/whatsapp/meta/test`.
- `routes/modules/webhook.php` — `GET/POST webhook/whatsapp/meta`.
- `resources/views/admin/gateway/whatsapp/edit.blade.php` — dropdown provider, field nama, hints Meta, webhook URL + verify token display.
- `resources/views/admin/gateway/whatsapp/index.blade.php` — kolom Provider badge + tombol Templates.
- `resources/views/admin/layout.blade.php` — menu sidebar Meta Templates.

## Verifikasi

- `php -l` semua file baru/diubah: OK.
- `php artisan route:list`: 262 routes (sebelumnya 258), route Meta terdaftar.
- `./vendor/bin/pint`: 8 style issues fixed.
- `composer test`: PASS 2/2.

## Follow-up

- Live test dengan kredensial Meta asli: webhook verification di HTTPS production, send message dengan Phone Number ID + Access Token, list templates dengan WABA ID.
- Cek panjang kolom `whatsapp_setting.sender` (15) cukup untuk Phone Number ID Meta (umumnya 15 digit, tetapi beberapa 16).
- Bot Meta hanya `/help` dan `/cek`; jika perlu parity dengan bot lama, port command admin lengkap.
- `WhatsAppGatewayResolver::makeFromActive()` return type hint `?WhatsAppApi` bisa salah jika active gateway Meta; pertimbangkan union type `WhatsAppApi|WhatsAppMetaApi|null`.
- Tambah flow create/add gateway Meta baru jika belum ada row; `whatsappAdd()` saat ini hanya simpan `whatsapp_number` dan mungkin tidak kompatibel.
