# ODP Port Assignment Hardening & Select2 Sync

- Agent: Droid
- Timestamp: 2026-07-20 17:44 WIB
- Scope: Form customer edit, sinkronisasi dropdown Select2, deteksi port terpakai, dan resolusi nama ODP legacy.

## Root cause
- Pada halaman edit customer, list dropdown `Port ODP` menggunakan Select2. Saat nama ODP diubah atau di-load pertama kali, option baru ditambahkan ke native `<select>` secara dinamis lewat AJAX, namun container Select2 tidak di-refresh/di-trigger. Akibatnya, status `disabled` dan penambahan keterangan `( Port Sudah digunakan Oleh : ... )` tidak ter-render/terbaca secara visual di dropdown Select2.
- Endpoint `/get-used-ports` menggunakan `uniqueByStoredName()` secara langsung untuk mencocokkan nama ODP legacy. Jika ada dua nama ODP yang bertabrakan pada 15 karakter pertama (misal `Aping001(P.Herpin)` dan `Aping001(P.Herpin_Dua)`), keduanya akan terfilter keluar oleh resolver prefix unik sehingga port terpakai pada ODP tersebut tidak terdeteksi, padahal salah satunya merupakan exact match penuh (bukan prefix terpotong).

## Perbaikan
- Memperbarui `resources/views/admin/customer/edit.blade.php` agar memanggil `.select2()` pada `#port-odp-select` setiap kali dropdown dikosongkan, di-disable, atau diisi ulang datanya. Ini memaksa Select2 memperbarui data cache-nya dan memblokir klik pada port yang di-disable secara visual.
- Memperbarui `getUsedPorts()` di `CustomerController.php` untuk memetakan exact full-name terlebih dahulu sebelum fallback ke pencocokan prefix 15-karakter yang unik. Cara ini konsisten dengan `OdpAssignment::resolve()` dan mencegah data port terpakai hilang akibat deteksi tabrakan prefix pada nama ODP lengkap.
- Menambahkan unit/feature test `test_get_used_ports_resolves_exact_names_first_when_prefix_collides` ke `CustomerLazyLoadingTest.php` untuk menjamin resolusi exact-name bekerja sempurna meskipun terjadi tabrakan prefix ODP.

## File utama
- `app/Http/Controllers/Admin/CustomerController.php`
- `resources/views/admin/customer/edit.blade.php`
- `tests/Feature/CustomerLazyLoadingTest.php`

## Validasi
- PHP Pint, Blade view cache, dan targeted tests PASS: `CustomerLazyLoadingTest` (10 passed, 46 assertions).
- Full suite PASS: 76 passed, 429 assertions, 1 skipped.
