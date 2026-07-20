# Context: Eager loading kecuali Customers dan Invoice Data

- **Tanggal**: 2026-07-20 12:22 WIB
- **Agent**: Droid
- **Bucket**: features

## Tujuan

Memulihkan data ODP/customer yang tampak kosong dan membatasi tombol `Tampilkan Data` hanya pada menu Customers dan Finance Invoice Data sesuai koreksi kebutuhan.

## Keputusan

- Semua halaman, tabel, laporan, dashboard, peta, dan endpoint polling kembali memuat data langsung tanpa `show_data`.
- Lazy loading dipertahankan hanya di `resources/views/admin/customer/index.blade.php` + endpoint Customer DataTables/filter dan `resources/views/admin/finance/invoice/index.blade.php` + endpoint statistik/data invoice.
- Coverage ODP sekarang langsung menjalankan agregasi `orders.nama_odp`, port terpakai, dan jumlah pelanggan saat halaman dibuka, sehingga assignment ODP tidak lagi terlihat kosong karena gate aktivasi.
- Keamanan signed public NMS tetap dipertahankan; default filter tahun 2026 juga tetap dipertahankan.
- Review menemukan dan memperbaiki koma yatim pada tiga payload AJAX laporan Finance serta dua directive Blade tidak seimbang.

## File yang Disentuh

- Controller lintas Admin/Server/User yang sebelumnya memiliki gate `show_data`, terutama `app/Http/Controllers/Admin/CoverageController.php`, `AdminController.php`, `FinanceController.php`, `NmsController.php`, `Server/RouterController.php`, `Server/OltController.php`, `Server/AcsController.php`, dan `Server/VoucherController.php`.
- View lintas dashboard/accounting/coverage/finance/NMS/router/OLT/ACS/voucher/user yang sebelumnya memiliki tombol atau conditional activation.
- Sembilan test feature lazy-loading diperbarui untuk menguji eager loading dan mempertahankan dua pengecualian.

## Follow-up

- Tidak ada blocker. Full suite: 51 passed, 342 assertions, 1 skipped.
- Optimasi opsional terpisah: batch status NMS dan agregasi laporan accounting/NMS untuk mengurangi fan-out setelah eager loading.
