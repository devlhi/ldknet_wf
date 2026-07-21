# Context: Bypass Transaksi Gateway Aktif untuk Konfirmasi Invoice Manual

- **Tanggal**: 2026-07-21 12:00 WIB
- **Agent**: Droid
- **Bucket**: modules-parallel

## Tujuan

Memungkinkan admin/developer/finance mengonfirmasi pembayaran manual untuk invoice yang masih memiliki transaksi Tripay/Duitku aktif, tanpa melemahkan pencegahan transaksi online ganda untuk pelanggan.

## Keputusan

- Proteksi transaksi gateway aktif tetap berlaku secara default, baik saat preflight maupun pengecekan ulang dengan row lock di dalam transaksi DB.
- Halaman edit hanya menampilkan checkbox `bypass_gateway` ketika transaksi gateway masih aktif. Admin harus mencentangnya secara eksplisit.
- Bypass berlaku untuk invoice sumber dan invoice tujuan pada mode maju satu bulan, tetapi pemeriksaan status, periode duplikat, nominal, paket, advisory lock, dan row lock tetap berlaku.
- Metadata transaksi gateway aktif dipertahankan setelah takeover manual untuk rekonsiliasi/audit callback. Callback terlambat tetap idempotent karena invoice sudah bukan `Unpaid`. Metadata transaksi expired tetap dibersihkan seperti sebelumnya.
- Setiap takeover aktif dicatat ke warning log dengan kode invoice, pelanggan, provider, reference, expiry, serta identitas admin.

## File yang Disentuh

- `app/Http/Controllers/Admin/FinanceController.php`
- `resources/views/admin/finance/invoice/edit.blade.php`
- `tests/Feature/FinanceInvoiceGatewayHardeningTest.php`

## Verifikasi

- Blade cache berhasil.
- Pint check berhasil.
- Targeted finance: 9 passed, 72 assertions.
- Full suite: 88 passed, 1 skipped, 507 assertions.

## Follow-up

- Verifikasi transaksi manual pada gateway sandbox/live bila kredensial dan invoice uji tersedia; tidak ada perubahan skema DB.
