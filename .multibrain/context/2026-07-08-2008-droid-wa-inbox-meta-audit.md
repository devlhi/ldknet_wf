# Audit WhatsApp Inbox Meta Official

Waktu: 2026-07-08 20:08 WIB
Agent: Droid

## Cakupan

Audit end-to-end untuk memastikan chat inbox Meta Official bisa berfungsi:
- Meta webhook verify & receive.
- Pesan masuk tersimpan ke `wa_inbox_messages`.
- Inbox admin menampilkan percakapan dan polling AJAX.
- Admin reply via Meta API.

## Hasil audit

OK:
- Route tersedia:
  - `GET webhook/whatsapp/meta` -> verify token Meta.
  - `POST webhook/whatsapp/meta` -> receive webhook Meta.
  - `GET admin/whatsapp/inbox` -> halaman inbox.
  - `GET admin/whatsapp/inbox/poll` -> polling AJAX 5 detik.
  - `POST admin/whatsapp/inbox/send` -> balas admin.
- `WebhookController::whatsappMeta()`:
  - Abaikan event non-message.
  - Cek webhook ON.
  - Cek gateway aktif dan provider Meta.
  - Dedup berdasarkan `meta_message_id` supaya retry webhook tidak membuat pesan dobel.
  - Simpan pesan masuk text/button/interactive/media/location ke `wa_inbox_messages`.
- `WhatsAppInboxController`:
  - Menampilkan conversation list + detail pesan.
  - 24h reply window dicek dari pesan masuk terakhir.
  - Poll endpoint mengembalikan pesan baru, conversations, dan status `can_reply_text`.
- `WhatsAppGatewayResolver::sender()` memakai Phone Number ID dari compact Meta blob (`api_url` index 8), bukan kolom sender varchar(15), jadi ID panjang aman.

## Perbaikan dilakukan

1. `app/Http/Controllers/Admin/WhatsAppInboxController.php`
   - Setelah `sendMessage()`, response JSON dari Meta dicek.
   - Jika ada `error`, admin mendapat `auth_errors` dan pesan tidak disimpan sebagai `sent` palsu.

2. `resources/views/admin/gateway/whatsapp/inbox.blade.php`
   - Tambah banner petunjuk setup jika Meta gateway belum aktif atau webhook OFF.
   - Banner menampilkan link cek gateway, cek webhook, dan webhook URL: `url('webhook/whatsapp/meta')`.

## Verifikasi

- `php -l app/Http/Controllers/Admin/WhatsAppInboxController.php` OK.
- `php -l app/Http/Controllers/WebhookController.php` OK.
- `php artisan route:list` route Inbox/Meta OK.
- `php artisan view:cache` OK.
- Pint PASS.
- PHPUnit PASS (3 tests, 2 assertions, 1 skipped).

## Petunjuk setup production

1. Di LandakNet buka `admin/whatsapp`.
2. Pastikan gateway Meta Official aktif `mode=ON`.
3. Isi dan simpan:
   - Graph URL, contoh `https://graph.facebook.com/v20.0`.
   - Access Token permanent/long-lived.
   - WABA ID.
   - Phone Number ID.
   - Verify Token.
4. Buka `admin/webhook`, pastikan status webhook `ON`.
5. Di Meta Developer Dashboard, set Callback URL: `https://domain-anda/webhook/whatsapp/meta`.
6. Verify token harus sama dengan Verify Token di gateway Meta.
7. Subscribe field WhatsApp `messages`.
8. Test kirim WA dari nomor pelanggan ke nomor official.
9. Buka `admin/whatsapp/inbox`, pesan harus muncul tanpa refresh penuh (polling 5 detik).
10. Admin bisa balas teks bebas hanya dalam 24 jam sejak pesan masuk terakhir. Jika lewat 24 jam, gunakan template message.
