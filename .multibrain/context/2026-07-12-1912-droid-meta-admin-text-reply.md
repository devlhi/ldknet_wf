# Context: Verifikasi balasan teks admin Meta Inbox

- **Tanggal**: 2026-07-12 19:12 WIB
- **Agent**: Droid
- **Bucket**: wa-gateway

## Tujuan

Memastikan admin benar-benar dapat membalas chat yang dikirim user melalui Meta Inbox, bukan hanya menampilkan form balasan.

## Keputusan

Action `WhatsAppInboxController::send()` tetap hanya mengizinkan balasan bebas saat pesan inbound terakhir kurang dari 24 jam. Respons Meta kini wajib berupa JSON sukses dengan `messages.0.id`; respons kosong/rusak/error tidak lagi disimpan sebagai pesan terkirim. WhatsApp Message ID balasan disimpan di `meta_message_id`. Test baru memverifikasi payload Graph API berisi nomor user, tipe text, isi balasan, signature admin, record outbound, dan tidak ada request Meta setelah jendela 24 jam habis.

## File yang Disentuh

- `app/Http/Controllers/Admin/WhatsAppInboxController.php`: validasi respons Meta dan simpan outbound Message ID.
- `tests/Feature/WhatsAppInboxMediaTest.php`: test reply teks sukses dan expired window.
- `app/Http/Controllers/WebhookController.php`: hardening deteksi kegagalan write image dari diagnosis sebelumnya.

## Follow-up

Lakukan live reply singkat pada percakapan baru di Meta produksi. Perubahan sesi 19:09 dan 19:12 belum commit/push.
