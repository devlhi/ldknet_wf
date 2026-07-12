# Context: Kirim Gambar Admin di Jendela Meta 24 Jam

- **Tanggal**: 2026-07-12 18:40 WIB
- **Agent**: Droid
- **Bucket**: wa-gateway

## Tujuan

Memungkinkan admin/developer mengirim JPEG atau PNG kepada pelanggan dari WhatsApp Inbox selama jendela layanan pelanggan Meta 24 jam masih aktif.

## Keputusan

Tidak menambah atau mengubah schema database. Gambar divalidasi sebagai JPEG/PNG aktual maksimal 5 MB, di-upload langsung ke endpoint Meta `/{phone_number_id}/media`, lalu dikirim memakai Media ID melalui endpoint messages. Pengiriman ditolak server-side bila pesan masuk terakhir sudah lebih dari 24 jam. Setelah Meta mengembalikan WhatsApp message ID, record `wa_inbox_messages` dibuat sebagai `direction=out`, `message_type=image`, dan salinan gambar disimpan pada private storage menggunakan hash message ID agar dapat dipreview di riwayat admin. Endpoint media private kini melayani gambar inbound maupun outbound, tetap di dalam middleware auth admin/developer.

## File yang Disentuh

- `app/Libraries/WhatsAppMetaApi.php`: upload media multipart dan kirim image dengan Media ID.
- `app/Http/Controllers/Admin/WhatsAppInboxController.php`: action `sendImage`, validasi, enforcement presisi 24 jam, penyimpanan salinan privat, dan stream outbound.
- `resources/views/admin/gateway/whatsapp/inbox.blade.php`: form upload gambar hanya saat jendela aktif dan preview chat.
- `routes/modules/gateway.php`: route POST `admin/whatsapp/inbox/send-image`.
- `tests/Feature/WhatsAppInboxMediaTest.php`: test upload/send/preview serta penolakan setelah 24 jam.

## Follow-up

Lakukan live test dengan kredensial Meta dan Phone Number ID produksi untuk memastikan permission access token mencakup upload media dan messages. Template tidak diperlukan selama jendela 24 jam aktif.
