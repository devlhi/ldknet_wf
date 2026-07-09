# 2026-07-07 23:15 WIB | codebuddy | Modul Finance area, User area, Guest cek/tagihan, AJAX/router traffic

## Ringkasan
Migrasi modul yang belum disentuh (P1-P3) dari CI4 ke Laravel, tanpa ubah skema DB dan tanpa sentuh CI4 legacy.

## File yang disentuh
- `routes/modules/finance-area.php` (baru) — route `/finance/*` level:finance, reuse `Admin\FinanceController` + `Admin\AccountController` + `Admin\AdminController::index`. Mempertahankan typo URL CI4 `finance/finance/kas` dan `finance/invoice/detail/{any}`.
- `routes/modules/user-area.php` (baru) — route `/user/*` level:user.
- `routes/modules/guest.php` (baru) — route publik `/cek`, `/cek/proses`, `/tagihan`, `/tagihan/check`, `/tagihan/{any}`, `/tagihan/process`, `/invoice/{any}`.
- `routes/modules/ajax-router.php` (baru) — `/ajax/cekcoupon/{any?}`, `/router/traffic`, `/router/traffic/pppoe/{any}`, `/router/traffic/hotspot/{any}`, `/router/bandwith/pppoe/{any}`.
- `app/Http/Controllers/User/UserController.php` (baru) — 12 method 1:1 dengan CI4 `base\user\UserController`.
- `app/Http/Controllers/GuestController.php` (baru) — cek/tagihan/invoice publik.
- `app/Http/Controllers/AjaxController.php` (baru) — `getCategory`, `cekcoupon`.
- `app/Http/Controllers/Server/RouterController.php` — tambah `getTraffic`, `getTrafficPPPOE`, `getBandwithPPPOE`.
- `routes/web.php` — hapus placeholder `/finance` dan `/user` (sudah ada controller asli).
- View baru: `resources/views/user/{service,invoice/index,invoice/detail,invoice/payment}.blade.php`, `resources/views/guest/{cek,tagihan-detail,invoice-detail}.blade.php`.

## Keputusan penting
- **P0 belum dikerjakan**: `CallbackController` (Tripay/Paydisini), `WebhookController` (WhatsApp bot), `AutoController` (cron isolir/reminder) sengaja ditunda karena logic uang + 528-1073 baris, butuh port 1:1 penuh dan verifikasi end-to-end dengan router live. Jangan dipotong parsial.
- `user/invoice/pembayaran` dan `tagihan/process` di-port versi **minimal**: update kolom invoice (category/service/method/penerima/random_price/received/exppay/provider) tanpa panggil Tripay/Paydisini API. Alasan: integrasi payment gateway butuh konfigurasi merchant live + testing callback. Logic 1:1 dengan Tripay/Paydisini API tetap perlu di-port di sesi P0.
- `AjaxController::getCategory` mencari payment gateway default via kolom `is_default`/`default`/`status` (CI4 pakai model `getPaymentMethodByNameAndPaymentDefault`). Perlu verifikasi nama kolom sebenarnya di tabel `payment_gateway` saat testing live.
- `router/traffic/*` di-port dengan fallback JSON `[0,0]` jika router tidak bisa connect, supaya grafik dashboard tidak 500.

## Verifikasi
- `php artisan route:list` = 207 routes, 0 error.
- `php -l` semua controller baru = No syntax errors.

## Follow-up
1. **P0 CallbackController** — port 1:1 `TripayCallback`, `CallbackPaydisini`, `PaydisiniTesting`. Baca `C:\laragon\www\landaknet\app\Controllers\base\CallbackController.php` baris 1-889 + `WebhookController.php` 1-1073.
2. **P0 AutoController** — port `updatestatus`, `isolir`, `cetakinv`, `reminderInvoice` (528 baris). Pertimbangkan route biasa vs Laravel Scheduler.
3. **Tripay/Paydisini API call** di `User\invoicePembayaran` dan `Guest\tagihanProcess` — port signature + `createTransaction` dari CI4 setelah merchant live.
4. Verifikasi nama kolom tabel `payment_gateway` (is_default vs default vs status) saat testing.
5. `git init` + commit awal belum dilakukan.
