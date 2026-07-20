# Context: Konfirmasi Manual Maju 1 Bulan (Cancel July, Paid August) & Callback Hardening

- **Timestamp**: 2026-07-20 16:18 WIB
- **Agent/Tool**: GitHub Copilot (mycombo)
- **Modul**: Finance (Konfirmasi Manual Admin + Callback Payment Gateway + Advisory Locks)
- **Repo**: `landaknet-laravel`

## Ringkasan Perubahan

1. **Konfirmasi Manual Invoice Maju 1 Bulan (Sesuai Permintaan User)**:
   - Form `admin/finance/invoice/edit.blade.php` ditambahkan radio selection `confirmation_period`:
     - `current`: Konfirmasi bulan berjalan (tetap mempertahankan semantik lama).
     - `next`: Majukan 1 bulan.
   - Sesuai permintaan eksplisit user: **"untuk juli buat jadi cancel ya bukan unpaid lagi krena dia udah di hitung lgi dri tgl dia byr"**.
   - Di database, invoice sumber diubah statusnya menjadi `Error` (karena keterbatasan enum DB legacy yang di-share dengan CI4 tanpa schema change), dan seluruh view/blade memetakannya menjadi label **`Cancel`**.
   - Invoice periode tujuan (Agustus) dibuat/digunakan dengan status **`Paid`**.
   - Masa aktif (`orders.expdate`) dihitung satu bulan dari tanggal pembayaran (`CarbonImmutable::parse($date)->addMonthNoOverflow()`).

2. **Penguatan Concurrency & Advisory Locks (MySQL `GET_LOCK`)**:
   - `Invoice::lockName()` diperbaiki agar selalu menghasilkan nama lock $\le 64$ karakter (memakai hash `sha1`), sesuai batas ketat MySQL advisory locks.
   - Tiga skop lock terkoordinasi:
     - `paymentLockName(invoice_code)`
     - `periodLockName(idpel, Y-m)`
     - `customerAccessLockName(idpel)`
   - Driver check: otomatis `true` / no-op di SQLite untuk compatibility testing, aktif penuh di MySQL production.
   - Lock release dibungkus dalam blok `finally` non-fatal dengan logging try/catch.

3. **Callback Hardening (`CallbackController.php`)**:
   - `commitGatewayPayment()` atomik dengan advisory lock + DB transaction + `lockForUpdate`.
   - Idempotensi: duplicate callback tidak menduplikasi laporan pemasukan (`report`).
   - Logging konflik manual takeover: jika invoice berstatus `Error` dan callback reference cocok dengan stored reference, dicatat `Log::critical('Callback pembayaran datang setelah manual takeover.', [...])`.
   - Expiration no-regression rule: callback periode lama tidak memundurkan `orders.expdate` jika tanggal di order sudah lebih baru.
   - Buka isolir di RouterOS dipisahkan ke post-commit under `customerAccessLockName` dan di-gate oleh flag `was_isolir`.
   - Jika router gagal merespons, status order ditandai `Isolir` dengan expdate masa depan sebagai retry queue yang aman untuk `AutoController::isolir()`.
   - Post-commit side-effects (RouterOS & notifikasi) diisolasi agar tidak pernah menggagalkan respons 2xx ke payment gateway.

4. **Penyelarasan Expiration Window & cURL Timeout**:
   - 24-hour expiration window diselaraskan pada deadline yang sama (`now('Asia/Jakarta')->addDay()`) antara `exppay` lokal, Tripay `expired_time`, Duitku duration (1440m), dan Admin direct pay.
   - Bounded cURL connect timeout (10s) dan total timeout (30s) pada `TripayPayment` dan `DuitkuPayment`.

5. **Display Mapping Status `Error` -> `Cancel`**:
   - Diperbarui pada 9 blade view dan 3 controller (`AdminController`, `GuestController`, `UserController`) agar user dan admin melihat label `Cancel` untuk status DB `Error`.

## Verifikasi & Test Suite

- **Pint**: Semua file PHP terformat rapi sesuai standar Laravel Pint.
- **PHPUnit Full Suite**: `101 passed, 1 skipped (556 assertions)` — 100% green.
- **Blade Template Cache**: `php artisan view:cache` sukses tanpa syntax error.
- **Git Tree**: Belum di-commit/push sesuai instruksi user (**"jgn di push dlu cek dlu code anda buat"**).
