# ODP relation legacy compatibility

- Agent: Droid
- Timestamp: 2026-07-20 17:04 WIB
- Scope: relasi pelanggan/port/gangguan/display ODP, topologi ODC→ODP, visual peta, dan cek koneksi Mikrotik.

## Root cause
- Relasi ODP legacy bukan ID: `orders.nama_odp` dicocokkan dengan `odp.nama`.
- Schema live: `orders.nama_odp` varchar(15), sedangkan `odp.nama` varchar(255).
- Exact string match gagal untuk nama panjang yang terpotong, beda case, atau memiliki whitespace. Dampaknya mencakup statistik pelanggan/port, pilihan ODP saat edit, deteksi port terpakai, rename/delete ODP, gangguan massal, broadcast, dan nama ODP pada tampilan/export.

## Perbaikan
- Tambah `app/Support/OdpAssignment.php` untuk canonical stored-name 15 karakter, normalisasi, resolver exact-first, unique-prefix lookup, dan deteksi prefix ambigu.
- Coverage ODP menghitung pelanggan, port, dan gangguan terbuka berdasarkan identitas ODP canonical.
- Form Customer mengirim `odp_id`; backend menyimpan key 15 karakter secara eksplisit, memvalidasi kapasitas, serta menolak ODP/prefix/port ambigu.
- Endpoint port/used-port memakai ODP ID. Tabel Customer, Customer Map, RX Power, dan export menampilkan nama master bila resolusi unik.
- Add/update ODP menolak collision 15 karakter awal; rename memutakhirkan assignment pelanggan serta laporan gangguan full/truncated; delete mengenali assignment legacy.
- Gangguan massal memprioritaskan exact full-name untuk report, memakai unique legacy key untuk pelanggan terpotong, menghitung pelanggan aktif, dan broadcast memvalidasi ODP ID server-side.
- Blade Customer/Gangguan diubah untuk menggunakan ODP ID, bukan nama display yang mutable.
- Dengan izin user, ditambahkan tabel additive `coverage_odcs` dan `coverage_odp_assignments`, tanpa mengubah tabel legacy. Migrasi sudah diterapkan ke DB lokal.
- CRUD ODC tersedia; tambah ODP wajib memilih ODC, sedangkan ODP lama dapat di-assign melalui edit. ODC yang masih memiliki ODP tidak bisa dihapus.
- Peta Jaringan menampilkan kartu SVG ODC/ODP, jalur ODC→ODP, serta ikon tiang visual yang tersebar pada geometri kabel.
- Daftar Customer menampilkan User PPPoE untuk admin/developer dan tombol Cek Koneksi on-demand. Endpoint memakai RouterOS timeout 3 detik, satu attempt, dan hanya mengembalikan Online/Offline.
- Sidebar Coverage disederhanakan menjadi Peta Jaringan, ODP, dan Pelanggan; fungsi teknis tetap tersedia sebagai tombol halaman.

## Batasan data legacy
- Dua ODP yang 15 karakter awalnya sama, contoh `Gontang001(Mayam Heri)` dan `Gontang001(Mayam Anton)`, tidak dapat dibedakan dari nilai `orders.nama_odp` yang sudah terpotong. Data ambigu sengaja tidak ditebak dan perlu koreksi manual atau perubahan schema di luar scope ini.

## File utama
- `app/Support/OdpAssignment.php`
- `app/Http/Controllers/Admin/CoverageController.php`
- `app/Http/Controllers/Admin/CustomerController.php`
- `app/Http/Controllers/Admin/GangguanController.php`
- `app/Models/GangguanReport.php`
- `app/Models/{CoverageOdc,CoverageOdpAssignment}.php`
- `database/migrations/2026_07_20_16150{0,1}_*.php`
- `resources/views/admin/coverage/{odc,odp,peta,customers}.blade.php`
- `resources/views/admin/customer/edit.blade.php`
- `resources/views/admin/customer/index.blade.php`
- `resources/views/admin/gangguan/index.blade.php`
- `tests/Feature/{CoverageFunctionalityTest,CustomerLazyLoadingTest,GangguanBulkCloseTest}.php`

## Validasi
- Schema live diperiksa; dua tabel additive yang disetujui user dimigrasikan sukses, tabel legacy tidak diubah.
- PHP lint PASS untuk seluruh PHP yang diubah.
- Pint PASS (10 file), Blade view cache PASS, `git diff --check` PASS.
- Targeted: Coverage 15 PASS/52 assertions; Customer 10 PASS/46 assertions; Gangguan 6 PASS/36 assertions.
- Full suite: 75 PASS, 1 skipped, 426 assertions.
