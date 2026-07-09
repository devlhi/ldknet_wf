# 2026-07-08 00:45 WIB | codebuddy | Audit bug Laravel setelah sinkron legacy terbaru

## Ringkasan
Audit bug setelah port legacy terbaru ke Laravel.

## Verifikasi yang dijalankan
- `./vendor/bin/pint --test` → menemukan 35 style issues.
- `./vendor/bin/pint` → memperbaiki 35 style issues.
- `php artisan route:list` → 258 routes, tidak ada exception.
- Route-method audit manual/script → tidak ada controller/method route yang hilang.
- `composer test` → awalnya gagal karena SQLite `:memory:` belum punya tabel `website`; setelah fallback, PASS 2 tests / 2 assertions.

## Bug yang diperbaiki
1. `routes/modules/customers.php` route order bug: wildcard `customer/update/{any}` berada sebelum `customer/update/pppoe|odp|tikor`, sehingga endpoint khusus bisa tertangkap wildcard. Dipindah agar endpoint khusus didaftarkan dulu.
2. `AuthController::websiteData()` gagal di test env karena tabel `website` belum ada di SQLite `:memory:`. Ditambah fallback `Schema::hasTable('website')` sebelum `Website::first()`.
3. Style issue Pint pada 137 file diformat otomatis.

## Catatan audit
- Folder CI4 legacy tidak diubah.
- P0 Callback/Webhook/Auto masih perlu verifikasi live dengan payment gateway/router/WA asli, karena test lokal hanya bisa memverifikasi syntax/route/unit smoke test.
- `RouterosAPI.php` masih memiliki function internal bernama `encrypt`/`decrypt` dari library RouterOS, bukan helper global legacy yang dipakai DB. Pemakaian password DB di controller sudah memakai `legacy_decrypt()`.
