# Konvensi Migrasi CI4 → Laravel 12 (landaknet-laravel)

Sumber CI4: `C:\laragon\www\landaknet` — Target Laravel: `C:\laragon\www\landaknet-laravel`

## Wajib diikuti

1. **Layout admin**: semua halaman admin extend `admin.layout` (`resources/views/admin/layout.blade.php`):
   ```blade
   @extends('admin.layout')
   @section('content') ... @endsection
   @section('scripts') ... @endsection  {{-- optional page JS --}}
   @section('css') ... @endsection      {{-- optional page CSS --}}
   ```
   Layout butuh variabel: `$title`, `$titletext`, `$logo`. Gunakan helper controller `websiteData()` (lihat pola di `app/Http/Controllers/Admin/AdminController.php`).

2. **Controller**: namespace `App\Http\Controllers\Admin` (atau `App\Http\Controllers\Server` untuk OLT/Router). Satu controller per modul, method names mengikuti CI4 supaya mudah dibandingkan.

3. **Routes**: tiap modul punya file sendiri di `routes/modules/<modul>.php`, auto-required oleh `routes/web.php`. Format:
   ```php
   <?php
   use App\Http\Controllers\Admin\XxxController;
   use Illuminate\Support\Facades\Route;

   Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
       Route::get('xxx', [XxxController::class, 'index']);
       ...
   });
   ```
   URL path HARUS sama persis dengan CI4 (lihat `app/Config/Routes.php` CI4) supaya link sidebar & redirect lama tetap jalan. Pertahankan GET untuk delete (perilaku app lama).

4. **Flash messages**: error → `->with('auth_errors', ['pesan'])`, sukses → `->with('success', ['pesan'])`. JANGAN pakai key `errors` (bentrok ViewErrorBag). Di Blade:
   ```blade
   @if (session('auth_errors')) ... @foreach (session('auth_errors') as $err) ...
   @if (session('success')) ... @foreach (session('success') as $suc) ...
   ```

5. **Models**: sudah ada di `app/Models` (User, Order, Invoice, Member, Router, Service, Website, Company, Coupon, Katkas, Note, Notification, Odp, Olt, PaymentCat, PaymentGateway, PaymentMethod, Pool, Psb, Report, SettingVoucher, SmtpSetting, TemplateMessage, TokenUser, Voucher, WhatsappSetting). Semua `$timestamps = false`, `$guarded = ['id']`. Tabel TIDAK punya kolom created_at/updated_at.

6. **Helpers global** (autoload composer): `tanggal()`, `tgl_indo()`, `bulan_indo()`, `formatBytes()`, `random_code()`, dll. Fungsi `encrypt()`/`decrypt()` CI4 lama sudah di-rename `legacy_encrypt()`/`legacy_decrypt()` — dipakai untuk password router di DB.

7. **Libraries**: sudah di-port apa adanya ke `app/Libraries` (namespace `App\Libraries` sama seperti CI4): RouterosAPI, TripayPayment, Paydisini, PaymentDisini, HsgqAPI, WhatsAppApi, WablasApi, OtpApi, Mailer.

8. **View konversi**: `<?= base_url() ?>xxx` → `{{ asset('xxx') }}` untuk assets, `{{ url('xxx') }}` untuk link. `<?= $var ?>` → `{{ $var }}`. Form POST wajib `@csrf`. `session()->get('nama')` → `auth()->user()->nama`, `session()->get('email')` → `auth()->user()->email`, `session()->get('level')` → `auth()->user()->level`.

9. **Upload file**: CI4 `$this->request->getFile('x')` → Laravel `$request->file('x')`. Simpan ke `public/assets/...` yang sama (pakai `$file->move(public_path('assets/...'), $nama)`) supaya path di DB tetap valid untuk kedua aplikasi.

10. **JANGAN mengubah skema database.** Kedua aplikasi share DB `landaknet`.

11. **Validasi**: `$request->validate([...])` dengan pesan Indonesia sama seperti CI4.

12. **DataTables**: view CI4 memakai class `datatable` / id `datatable` yang diinisialisasi `assets/js/pages/datatables.init.js` — pertahankan struktur id/class tabel yang sama.
