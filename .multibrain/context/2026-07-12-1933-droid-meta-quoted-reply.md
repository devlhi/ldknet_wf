# Context: Reply pesan spesifik WhatsApp Meta

- **Tanggal**: 2026-07-12 19:33 WIB
- **Agent**: Droid
- **Bucket**: wa-gateway

## Tujuan

Menambahkan reply teks terhadap bubble pesan user tertentu seperti fitur reply/quote di WhatsApp, bukan hanya mengirim pesan baru ke percakapan.

## Keputusan

Tanpa perubahan schema DB. Inbound `meta_message_id` yang sudah tersimpan digunakan sebagai `context.message_id` pada payload Meta. Browser hanya mengirim local row ID lewat `reply_to_message_id`; controller memuat ulang dan memvalidasi target wajib inbound, nomor percakapan sama, dan memiliki Meta Message ID. Generic reply tetap berfungsi bila tidak memilih pesan. UI menambah tombol Balas pada bubble inbound, preview pesan terpilih, tombol batal, fokus textarea, serta tombol yang sama untuk pesan baru dari polling. Quote persisten pada riwayat admin tidak disimpan karena tidak ada kolom relasi, tetapi WhatsApp user menampilkan quote resmi dari Meta.

## File yang Disentuh

- `app/Libraries/WhatsAppMetaApi.php`: optional `context.message_id` pada text payload.
- `app/Http/Controllers/Admin/WhatsAppInboxController.php`: expose capability reply dan validasi target percakapan.
- `resources/views/admin/gateway/whatsapp/inbox.blade.php`: tombol Balas, composer quote preview, cancel, polling UI.
- `tests/Feature/WhatsAppInboxMediaTest.php`: context payload dan pencegahan reply lintas percakapan.

## Follow-up

Live test dengan pesan inbound baru: klik Balas pada bubble, ketik teks, pastikan WhatsApp pelanggan menampilkan quoted message. Perubahan belum commit/push.
