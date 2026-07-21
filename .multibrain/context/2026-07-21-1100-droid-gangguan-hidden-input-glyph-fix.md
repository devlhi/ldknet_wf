# Pembersihan Teks Hitam (Glyph Font) pada Baris Aksi Laporan Gangguan

- Agent: Droid
- Timestamp: 2026-07-21 11:00 WIB
- Scope: `resources/views/admin/gangguan/index.blade.php`.

## Root cause
- Pada tabel daftar Laporan Gangguan, tombol aksi cepat Tutup / Buka dibungkus dalam inline `<form class="d-inline swal-confirm">`.
- Nilai `<input type="hidden" name="status">` (`"selesai"` dengan panjang 7 karakter untuk aksi Tutup, atau `"diproses"` dengan panjang 8 karakter untuk aksi Buka) ikut ter-render inline oleh browser/tema di bawah konteks font ikon, menghasilkan teks hitam simbol / glyph (seperti `←📼≣🏀▭⇦⇧↑` atau `🖲⏰🧺⇧⇦⇧🖸`) persis di antara tombol `Status` dan tombol `Tutup`/`Buka`.

## Perbaikan
- Menambahkan class `d-none` dan attribute `id="status-quick-{{ $r->id }}"` pada `<form>` aksi cepat, lalu memindahkan tombol submit `<button>` ke luar form dengan atribut `form="status-quick-{{ $r->id }}"`.
- Elemen form dan hidden input sekarang tersembunyi total (`display: none !important`) tanpa kemungkinan bocor ke tampilan visual sel tabel, sementara aksi cepat `Tutup` dan `Buka` serta konfirmasi SweetAlert tetap berfungsi 100% normal.

## Validasi
- Blade views compiled successfully via `php artisan view:cache`.
- PHP Pint formatting PASS.
- Targeted tests PASS: `GangguanBulkCloseTest` (8 passed, 43 assertions).
- Full test suite PASS: 83 passed, 460 assertions, 1 skipped.
