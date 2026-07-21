# Context: Status transaksi online dan Meta first-contact

- **Tanggal**: 2026-07-21 20:53 WIB
- **Agent**: Droid
- **Bucket**: modules-parallel, wa-gateway

## Tujuan

Menampilkan detail/status transaksi Tripay/Duitku pada konfirmasi invoice tanpa membuat pembatalan lokal palsu, serta memperbaiki pesan awal Meta yang gagal di luar jendela layanan 24 jam.

## Keputusan

- Status invoice awal tetap lokal agar halaman edit tidak bergantung jaringan. Tombol `Cek Status Provider` memanggil endpoint admin ter-throttle dan adapter `InvoiceGatewayStatus` untuk Tripay/Duitku.
- Tidak ada tombol cancel provider karena API pembatalan belum terverifikasi. Opsi admin dilabeli jujur sebagai pengambilalihan manual; metadata transaksi dipertahankan dan UI memperingatkan VA/QR lama mungkin masih dapat dibayar.
- Callback invoice Unpaid sekarang wajib cocok dengan provider dan reference tersimpan sebelum settlement.
- Meta first-contact wajib memakai template remote berstatus APPROVED. Pesan bebas dari halaman Test Message, broadcast, dan gangguan massal diblok server-side bila tidak ada inbound <24 jam.
- Form first-contact mengikuti kontrak remote template (bahasa, jumlah parameter body, tombol URL dinamis) dan template listing mengikuti pagination Graph API.
- Mapping legacy `notif_pelanggan_baru` dinormalisasi ke approved `notif_daftar_berhasil` tanpa mengubah skema atau blob DB secara destruktif.
- Webhook Meta kini memproses status sent/delivered/read/failed dan Inbox memperbarui label status aktual melalui polling visible message.
- Signature Duitku inquiry/status/callback diperbarui ke HMAC-SHA256 sesuai dokumentasi API v2 April 2026.

## File yang Disentuh

- `app/Support/InvoiceGatewayStatus.php`
- `app/Http/Controllers/Admin/FinanceController.php`
- `app/Http/Controllers/CallbackController.php`
- `routes/modules/finance-admin.php`
- `resources/views/admin/finance/invoice/edit.blade.php`
- `app/Http/Controllers/Admin/GatewayController.php`
- `app/Support/WhatsAppNotifier.php`
- `app/Support/WhatsAppGatewayResolver.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Http/Controllers/Admin/WhatsAppInboxController.php`
- `resources/views/admin/gateway/whatsapp/{message,meta-templates,inbox}.blade.php`
- `tests/Feature/{FinanceInvoiceGatewayHardeningTest,WhatsAppGatewayFlowsTest,WhatsAppInboxMediaTest}.php`

## Validasi

- Focused payment/WhatsApp: 35 test PASS, 186 assertion; Gangguan: 8 PASS, 43 assertion.
- Full suite: 97 PASS, 1 skip, 540 assertion.
- `php vendor/bin/pint --dirty`, `php artisan view:cache`, dan `git diff --check` PASS.

## Follow-up

- Jika Tripay/Duitku menyediakan endpoint cancel resmi untuk akun merchant ini, implementasikan pembatalan remote terverifikasi sebelum menawarkan tombol `Batalkan Provider`.
- Pastikan webhook Meta production mengirim subscription field `messages` agar delivery statuses masuk.
