# Perbaikan Edit Layanan Customer dan Halaman Generate Invoice

- Agent: Droid
- Timestamp: 2026-07-21 10:00 WIB
- Scope: `CustomerController::customerUpdateData()`, `resources/views/admin/finance/invoice/generate.blade.php`, dan tests.

## Root cause
1. **Layanan Customer Update**: Ketika admin mengubah layanan pelanggan, jika profile paket pada Mikrotik tidak ada/berbeda dari database, Mikrotik RouterOS API melempar error `Input does not match any value of profile`. Pesan ini diteruskan mentah-mentah ke user, membingungkan mereka. Juga, mode `hotspot` tidak di-handle saat mengubah paket (hanya command `/ppp/secret` yang dipanggil).
2. **Generate Invoice**: Halaman `/admin/finance/invoice/generate` tampil kosong/blank. Hal ini disebabkan oleh sintaks Blade yang salah: blok `@if (session('auth_errors'))` tidak ditutup (`@endif`), menyebabkan seluruh konten kartu form masuk ke dalam kondisi alert error dan tidak dirender saat GET biasa.

## Perbaikan
1. **CustomerController**:
   - Memodifikasi `customerUpdateData` untuk fallback ke paket customer yang aktif jika input paket kosong (agar admin bisa ganti status saja tanpa error).
   - Menambahkan pengecekan mode customer (`pppoe` atau `hotspot`) dan memanggil command RouterOS yang sesuai.
   - Menambahkan validasi jika user tidak ditemukan di Mikrotik, lalu menampilkan pesan error informatif dalam Bahasa Indonesia.
   - Menambahkan deteksi pesan trap error Mikrotik terkait "profile". Jika profil tidak ditemukan di Mikrotik, sistem mengembalikan pesan informatif: `Profile Mikrotik '{profile}' untuk paket ini tidak ditemukan pada router. Harap buat profile di Mikrotik atau sinkronkan ulang di menu Data Paket.`.
   - Mengaktifkan kembali user di Mikrotik (`enable`) jika status diubah ke `Active`, serta menghapus session aktif (`/ppp/active/remove` atau `/ip/hotspot/active/remove`) agar profil baru langsung terpasang.
2. **Blade view generate.blade.php**:
   - Menambahkan `@endif` untuk menutup alert error session di awal pembungkus form, dan menghapus `@endif` penutup yatim di bagian bawah form.
3. **Feature Tests**:
   - Menambahkan `test_customer_update_data_handles_profile_error_gracefully` dan `test_customer_update_data_updates_package_and_status` di `CustomerLazyLoadingTest.php`.
   - Menambahkan `test_generate_invoice_view_renders_form_properly` di `FinanceLazyLoadingTest.php`.

## Validasi
- Seluruh targeted tests PASS:
  - `CustomerLazyLoadingTest`: 13 passed (58 assertions)
  - `FinanceLazyLoadingTest`: 4 passed (25 assertions)
- Kompilasi cache Blade view sukses (`php artisan view:cache`).
- Full test suite PASS: 81 passed, 450 assertions, 1 skipped.
