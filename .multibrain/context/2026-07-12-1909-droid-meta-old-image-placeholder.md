# Context: Placeholder gambar Meta lama

- **Tanggal**: 2026-07-12 19:09 WIB
- **Agent**: Droid
- **Bucket**: wa-gateway

## Tujuan

Mendiagnosis screenshot inbox yang masih menampilkan `[Image]` tanpa preview setelah fitur media Meta dipasang.

## Keputusan

Pesan pada screenshot bertanggal 12 Jul 12:47, sebelum fitur unduh media dipasang sekitar 18:15. Record lama hanya menyimpan WhatsApp message ID, sedangkan Media ID gambar dari payload webhook dahulu dibuang. Meta membutuhkan Media ID untuk metadata/download dan URL medianya sementara, sehingga gambar lama tidak dapat dipulihkan dari record DB. Pelanggan perlu mengirim ulang gambar setelah deployment. Penyimpanan gambar baru diperkuat dengan memeriksa return value `Storage::put()` karena disk local memakai `throw=false`; kegagalan tulis kini menghasilkan warning hash message ID, tidak gagal diam-diam.

## File yang Disentuh

- `app/Http/Controllers/WebhookController.php`: deteksi dan log kegagalan write private media.

## Follow-up

Minta pelanggan mengirim ulang satu gambar baru untuk live verification. Pastikan `storage/app/private` writable oleh PHP/web server di server deployment. Perubahan hardening ini belum di-commit/push.
