# Fix Logic Pembayaran Invoice & Open Isolir

Waktu: 2026-07-08 19:48 WIB
Agent: Droid

## Latar belakang

Audit customer/pembayaran menemukan:
- Status invoice Paid/Unpaid dicount dengan casing salah (`paid`/`unpaid` vs data `Paid`/`Unpaid`).
- Pembaruan invoice, order, dan Report pemasukan hanya jalan kalau email Brevo sukses. Kalau email gagal, pembayaran gagal padahal uang sudah diterima.
- Notifikasi WhatsApp "terbayar" dikirim sebelum DB diupdate.
- Path Isolir (PPPoE/Hotspot) tidak insert Report pemasukan, jadi laporan kas kurang.

## Perubahan

File: `app/Http/Controllers/Admin/FinanceController.php`

1. Counter invoice `invoice()` dan `ambilDataInvoice()` sekarang pakai `Paid`/`Unpaid` (sebelumnya `paid`/`unpaid`).

2. `invoiceUpdate()` ketiga path (Isolir PPPoE, Isolir Hotspot, non-Isolir) diubah urutan logic:
   - Update Invoice + Order + Report pemasukan duluan setelah router berhasil membuka isolir.
   - Kirim WA notifikasi terbayar setelah DB sukses.
   - Kirim email Brevo dengan try/catch. Kalau gagal, log warning, pembayaran tetap sukses dan redirect `success`.
   - Semua path Isolir sekarang insert Report pemasukan, tidak hanya path non-Isolir.

3. Email gagal tidak lagi membatalkan pembayaran atau open isolir.

## Yang TIDAK diubah

- Route callback/webhook (`/callback/*`, `/webhook/*`) tetap 1:1.
- DB skema tidak diubah.
- Tidak memindahkan delete customer/invoice ke POST (belum diminta user).

## Verifikasi

- `php -l FinanceController.php` OK.
- `php artisan route:list` 273 routes OK.
- `php artisan view:cache` OK.
- Pint PASS.
- PHPUnit PASS (3 tests, 2 assertions, 1 skipped).
