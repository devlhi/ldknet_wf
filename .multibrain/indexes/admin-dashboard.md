# Sub-Index: `admin-dashboard`

Layout admin (sidebar+topbar) dan halaman dashboard.

## Rules

- Entry baru di ATAS.
- 1 baris per entry.

## Entries

- 2026-07-10 WIB | claude-fable-5 | FIX tab "Pelanggan Baru" (grafik + kartu) selalu 0. `AdminController::index` baca dari model `Psb` (tabel `psb` legacy tidak terisi — DB live: 0 baris tahun ini) padahal pendaftaran pelanggan baru masuk ke tabel `orders` (471 tahun ini, 25 bulan ini). Ubah `$totalpsb` (baris 39) & `$newCustomersByMonth` (baris 59) ke model `Order` (grup `MONTH(date)`), konsisten dgn `FinanceController::getFilteredDataNewCustomers()`. Import `Psb`→`Order`. Verifikasi query live: totalpsb=25, grafik Jan..Jul terisi (54,62,79,92,82,77,25), total=471. Pint + suite PASS.
- 2026-07-08 01:10 WIB | claude-9router | Perbaiki multi-tab dashboard: ganti `nav-tabs-custom nav-justified` ke `card-header-tabs` standar (tombol kiri atas di header card).
- 2026-07-08 00:50 WIB | claude-9router | Grafik dashboard dijadikan multi-tab (Keuangan / Status Invoice / Pelanggan Baru / Transaksi) dalam 1 card, render ulang chart pada `shown.bs.tab`.
- 2026-07-08 00:30 WIB | claude-9router | Tambah grafik dashboard (ApexCharts): pemasukan vs pengeluaran (area), status invoice (donut), pelanggan baru (bar) di `admin/home.blade.php` + data 12 bulan di `Admin\AdminController::index`.
- 2026-07-07 14:05 WIB | claude-fable-5 | Layout `resources/views/admin/layout.blade.php` + dashboard `Admin\AdminController` selesai (query credit/debit/gettransaction/totalpsb + tabel transaksi).
