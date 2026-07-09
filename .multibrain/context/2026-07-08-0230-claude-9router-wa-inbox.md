# WhatsApp Inbox Native

Tanggal: 2026-07-08 02:30 WIB
Agent: claude-9router

## Ringkasan

Menambahkan modul WhatsApp Inbox sederhana agar admin bisa melihat percakapan user dan membalas dari web LandakNet tanpa keluar aplikasi.

## File penting

- `database/migrations/2026_07_08_000001_create_wa_inbox_messages_table.php` - tabel baru `wa_inbox_messages` untuk pesan masuk/keluar.
- `app/Models/WaInboxMessage.php` - model pesan inbox.
- `app/Http/Controllers/WebhookController.php` - webhook Meta kini menyimpan pesan masuk ke inbox dan menyimpan auto-reply jika command bot dibalas.
- `app/Http/Controllers/Admin/WhatsAppInboxController.php` - halaman inbox dan action kirim balasan.
- `routes/modules/gateway.php` - route `admin/whatsapp/inbox` dan `admin/whatsapp/inbox/send`.
- `resources/views/admin/gateway/whatsapp/inbox.blade.php` - UI inbox: daftar percakapan, thread, detail user, form balas.
- `resources/views/admin/layout.blade.php` - menu sidebar WhatsApp Gateway > Inbox Chat.

## Catatan teknis

- Tidak mengubah tabel legacy existing. Hanya tambah tabel baru aplikasi Laravel.
- Balasan teks bebas dibatasi 24 jam sejak pesan masuk terakhir, sesuai customer service window WhatsApp Cloud API.
- Saat jendela 24 jam habis, UI menampilkan peringatan untuk pakai template message.
- Matching user memakai `users.nomor` dengan format `62...` dan fallback `0...`.

## Verifikasi

- `php artisan migrate --force` PASS.
- `php artisan route:list --path=whatsapp` PASS, route inbox terdaftar.
- `vendor/bin/pint --dirty` PASS.
- `vendor/bin/phpunit` PASS (2 tests, 2 assertions).

## Follow-up

- Live test dengan webhook Meta HTTPS dan pesan WhatsApp asli.
- Tambahkan support media download jika dibutuhkan.
- Tambahkan template reply dari inbox untuk percakapan > 24 jam.
