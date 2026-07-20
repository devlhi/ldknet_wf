# Pengetatan Deteksi & Pembersihan Laporan Gangguan Otomatis

- Agent: Droid
- Timestamp: 2026-07-20 17:50 WIB
- Scope: Model GangguanReport, pembersihan database laporan gangguan gibberish/lone wifi, pengetatan kata kunci deteksi gangguan masuk.

## Root cause
- Sebelumnya, kata kunci `'wifi'` dan `'wi-fi'` berdiri sendiri sebagai keyword kategori `wifi`. Akibatnya, pesan yang hanya menyebut "wifi" atau pesan biner/unicode/emoji yang mengandung pecahan kata "wifi"/"lag" otomatis terbuat sebagai laporan gangguan dan memicu balasan otomatis (*auto reply*).
- Pesan tanpa huruf (misal teks wingdings/simbol/emoji murni) sebelumnya tidak difilter sehingga masuk ke laporan gangguan kategori "lainnya".

## Perbaikan
- Memperbarui `GangguanReport::classify()` di `app/Models/GangguanReport.php`:
  1. Menolak teks yang tidak mengandung karakter huruf sama sekali (`!preg_match('/\p{L}/u', $t)`), sehingga simbol murni / emoji / wingdings diabaikan.
  2. Menolak teks yang hanya berupa kata "wifi" atau "wi-fi" saja (`$t === 'wifi' || $t === 'wi-fi'`).
  3. Menghapus keyword berdiri sendiri `'wifi'` dan `'wi-fi'` dari `KATEGORI['wifi']`, dan menggantinya dengan frasa keluhan yang jelas seperti `wifi tidak bisa`, `tidak bisa wifi`, `wifi mati`, `wifi putus`, `wifi lemot`, `wifi lambat`, `wifi bermasalah`, `wifi rusak`.
- Membuat migrasi `database/migrations/2026_07_20_180000_clean_gibberish_gangguan_reports.php` untuk membersihkan laporan lama di database yang pesannya kosong, tidak mengandung huruf, atau hanya berupa "wifi".
- Menambahkan feature tests di `tests/Feature/GangguanBulkCloseTest.php` untuk menguji klasifikasi dan logika pembersihan laporan gangguan.

## Validasi
- Migrasi dieksekusi sukses: `2026_07_20_180000_clean_gibberish_gangguan_reports`.
- PHP Pint, Blade view cache, dan targeted tests PASS: `GangguanBulkCloseTest` (8 passed, 47 assertions).
- Full test suite PASS: 78 passed, 440 assertions, 1 skipped.
