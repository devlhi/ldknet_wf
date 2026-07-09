# Sub-Index: `admin-dashboard`

Layout admin (sidebar+topbar) dan halaman dashboard.

## Rules

- Entry baru di ATAS.
- 1 baris per entry.

## Entries

- 2026-07-08 01:10 WIB | claude-9router | Perbaiki multi-tab dashboard: ganti `nav-tabs-custom nav-justified` ke `card-header-tabs` standar (tombol kiri atas di header card).
- 2026-07-08 00:50 WIB | claude-9router | Grafik dashboard dijadikan multi-tab (Keuangan / Status Invoice / Pelanggan Baru / Transaksi) dalam 1 card, render ulang chart pada `shown.bs.tab`.
- 2026-07-08 00:30 WIB | claude-9router | Tambah grafik dashboard (ApexCharts): pemasukan vs pengeluaran (area), status invoice (donut), pelanggan baru (bar) di `admin/home.blade.php` + data 12 bulan di `Admin\AdminController::index`.
- 2026-07-07 14:05 WIB | claude-fable-5 | Layout `resources/views/admin/layout.blade.php` + dashboard `Admin\AdminController` selesai (query credit/debit/gettransaction/totalpsb + tabel transaksi).
