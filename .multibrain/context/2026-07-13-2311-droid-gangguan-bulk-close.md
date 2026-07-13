# Context: Hapus ikon rusak & implementasi bulk close laporan gangguan

- **Tanggal**: 2026-07-13 23:11 WIB
- **Agent**: Droid
- **Bucket**: features

## Tujuan

User meminta:
1. Menghapus ikon-ikon bermasalah di tabel/halaman Laporan Gangguan (karena mdi font tidak ter-load sempurna sehingga muncul sebagai glyph baterai/jam/arah panah yang merusak tampilan).
2. Membuat fitur **Bulk Close** (Tutup Massal): admin bisa mencentang laporan terbuka di halaman saat ini untuk ditutup bersamaan, serta opsi menutup **seluruh** laporan terbuka di dalam rentang periode dan filter aktif sekaligus.

## Keputusan

- **Ikon Dihapus**: Menyingkirkan tag `<i class="mdi mdi-...">` di file `resources/views/admin/gangguan/index.blade.php`. Aksi per-baris (Balas, Status, Buka, Tutup) sekarang berupa tombol teks bersih. Rekapitulasi di atas tabel juga bersih dari ikon mdi.
- **Bulk Close Terpilih (Halaman Ini)**: Checkbox `ids[]` di setiap baris laporan terbuka (status 'baru' atau 'diproses'). Dikendalikan JS via ID `gg-check-all` dan class `.gg-row-check`. Tombol "Tutup Terpilih" akan aktif dan menampilkan jumlah yang dicentang.
- **Bulk Close Semua Terbuka (Sesuai Filter)**: Menambahkan tombol "Tutup Semua Terbuka ({count})" yang muncul bila ada laporan terbuka dalam periode/filter yang aktif. Tombol memicu form `#bulk-close-all-form` dengan input `close_all = 1`.
- **Controller & Keamanan**:
  - `GangguanController::bulkClose()` menerima `ids` (array ID) atau `close_all` (boolean).
  - Jika `close_all` aktif, ia mengambil seluruh laporan berstatus `baru`/`diproses` dalam rentang tanggal periode (`start` dan `end`) serta filter `status` dan `kategori` yang dikirim dari form.
  - Database transaksi dibungkus dengan `lockForUpdate()`. SLA timestamp (`responded_at` dan `resolved_at`) diisi otomatis dengan waktu `now()` jika masih null.
  - Membatasi hak akses hanya untuk level `admin` dan `developer` melalui middleware route.
- **Redirect Pintar**: Mengembalikan admin ke halaman index gangguan dengan tetap mempertahankan parameter filter (`periode`, `tanggal`, `status`, `kategori`) menggunakan query string.

## File yang Disentuh

- `app/Http/Controllers/Admin/GangguanController.php` (menghitung `$openFilteredCount` & logic `bulkClose`)
- `resources/views/admin/gangguan/index.blade.php` (hapus ikon mdi, layout tombol aksi, form bulk-close & bulk-close-all, script JS select-all)
- `routes/modules/gangguan.php` (route POST `admin/gangguan/bulk-close`)
- `tests/Feature/GangguanBulkCloseTest.php` (fitur pengujian tutup terpilih, tutup semua filter, hak akses, validasi input minimal)

## Verifikasi

- `php artisan test --filter=GangguanBulkCloseTest` → 4 PASS / 33 assertions.
- `php artisan test` → 15 PASS / 1 skip (smoke mysql) / 109 assertions.
- `php vendor\bin\pint --dirty` → OK (0 issue). `php artisan view:cache` → OK.
