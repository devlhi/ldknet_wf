# Pembersihan Teks Hitam (Glyph Font) pada Header Form Tutup Terpilih Laporan Gangguan

- Agent: Droid
- Timestamp: 2026-07-21 11:20 WIB
- Scope: `resources/views/admin/gangguan/index.blade.php`.

## Root cause
- Dua baris teks hitam simbol/glyph font (`←📼≣🏀▭⇦⇧↑` dan `🖲⏰🧺⇧⇦⇧🖸`) yang masih muncul tepat di bawah judul card "Riwayat Laporan (July 2026)" berasal dari form `id="bulk-close-form"` dan `id="bulk-close-all-form"`.
- Form tersebut sebelumnya tidak memiliki class `d-none` (Bootstrap `display: none !important`), sehingga input tersembunyi (`@csrf`, `periode`, `tanggal`, `f_status`, `f_kategori`) di dalamnya ikut dirender secara visual sebagai glyph font oleh browser/tema.

## Perbaikan
- Menambahkan class `d-none` pada form `bulk-close-form` dan `bulk-close-all-form`.
- Tombol submit "Tutup Terpilih" dan "Tutup Semua Terbuka" tetap terhubung secara aman via atribut HTML5 `form="bulk-close-form"` dan `form="bulk-close-all-form"` tanpa ada elemen form tersembunyi yang bocor ke tampilan UI.

## Validasi
- Blade views compiled successfully via `php artisan view:cache`.
- PHP Pint formatting PASS.
- Full test suite PASS: 83 passed, 460 assertions, 1 skipped.
