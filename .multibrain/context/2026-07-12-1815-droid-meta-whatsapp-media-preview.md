# Context: Unduh Media & Preview Gambar WhatsApp Meta

- **Tanggal**: 2026-07-12 18:15 WIB
- **Agent**: Droid
- **Bucket**: wa-gateway

## Tujuan

Memungkinkan admin melihat kiriman gambar dari pelanggan di WhatsApp Meta dalam chat inbox secara aman tanpa mempublikasikan URL internal Meta atau mengubah skema database bersama.

## Keputusan

1. **Unduh Gambar Otomatis**: Saat tipe pesan masuk dari webhook adalah `image`, unduh file gambar asli menggunakan metadata URL Meta Graph ke private storage `wa-inbox/{sha256(meta_message_id)}`.
2. **Idempotensi & Retry**: Jika webhook mengalami retry oleh Meta, database tidak digandakan. Bila file sebelumnya belum terunduh sempurna, dicoba unduh kembali.
3. **Endpoint Privat**: Gambar yang terunduh di-stream lewat route privat terautentikasi `admin/whatsapp/inbox/media/{message}`. Hanya admin/developer terautentikasi yang bisa mengakses.
4. **Keamanan Logging**: Menghapus `\Log::info('WhatsApp Meta webhook payload', $payload)` untuk menghindari kebocoran kredensial pelanggan atau metadata dalam file log.
5. **UI Rendering**: Tag `<img>` ditambahkan ke layout rendering Blade dan JavaScript polling inbox.

## File yang Disentuh

- `app/Libraries/WhatsAppMetaApi.php`: helper metadata & download media.
- `app/Http/Controllers/WebhookController.php`: hook download & hapus log payload webhook mentah.
- `app/Models/WaInboxMessage.php`: static helper path & media check.
- `app/Http/Controllers/Admin/WhatsAppInboxController.php`: media streaming & poll URL injector.
- `routes/modules/gateway.php`: register route media.
- `resources/views/admin/gateway/whatsapp/inbox.blade.php`: view & polling js update.
- `tests/Feature/WhatsAppInboxMediaTest.php`: test suite media download & private access control.

## Follow-up

Pastikan folder `storage/app/private/wa-inbox/` memiliki permission write bagi user web server.
