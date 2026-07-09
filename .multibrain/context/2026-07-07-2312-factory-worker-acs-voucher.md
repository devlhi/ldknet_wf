# 2026-07-07 23:12 WIB | factory-worker | Modul ACS dan Voucher

## Ringkasan
Migrasi modul Server (ACS / GenieACS) dan Voucher dari CI4 ke Laravel, tanpa ubah skema DB dan tanpa sentuh CI4 legacy. Menggunakan Eloquent dan Query Builder (`DB::table`) secara 1:1.

## File yang disentuh
- `routes/modules/acs.php` (baru) — routes untuk ACS (`server/acs/*`) level:admin,developer.
- `routes/modules/voucher.php` (baru) — routes untuk Voucher (`server/voucher/*`) level:admin,developer.
- `app/Http/Controllers/Server/AcsController.php` (baru) — 10 method 1:1 dengan CI4 `ACSController` menggunakan HTTP Client via custom helper.
- `app/Http/Controllers/Server/VoucherController.php` (baru) — 8 method 1:1 dengan CI4 `VoucherController`.
- `app/Libraries/ACSRequest.php` (baru) — Wrapper Curl untuk request GenieACS, diport langsung dari CI4.
- View baru: `resources/views/admin/acs/{home,dashboard,modem}.blade.php` dan `resources/views/admin/voucher/{home,report,users,template}.blade.php`.

## Keputusan penting
- `ACSRequest.php` API Wrapper diport apa adanya menggunakan `curl` bawaan seperti di CI4 untuk meminimalkan risiko ketidakcocokan parameter dan headers pada GenieACS API.
- ACS Views (`dashboard`, `modem`) menggunakan Blade syntax. Parsing script `<script>` dan `@push('scripts')` diganti menggunakan `@section('scripts')` yang sesuai dengan `admin.layout`.
- Flash message menggunakan `auth_errors` dan `success` sesuai konvensi.
- `VoucherController`: logic filtering dengan `response()->json` untuk AJAX dipindahkan secara presisi (chartData, total_harga, dll).

## Verifikasi
- `php artisan route:list` = list lengkap, 0 error. (Ada penambahan 18 route dari ACS & Voucher).
- `php -l` controller = No syntax errors.

## Follow-up
- Uji integrasi end-to-end ACSRequest ke GenieACS asli untuk memverifikasi request parameter JSON saat koneksi Router aktif.
