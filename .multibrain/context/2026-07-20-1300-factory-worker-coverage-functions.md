# Coverage ODP, Area, ODC, dan peta pelanggan

- Agent: factory-worker
- Timestamp: 2026-07-20 13:00 WIB
- Scope: routes/controller/views coverage dan feature test terisolasi.

## Hasil
- Endpoint hapus ODP/Area kini POST + CSRF dan menolak penghapusan data yang masih direferensikan.
- ODP divalidasi server-side; statistik port dinormalisasi/dedup; rename memperbarui `orders.nama_odp` dan `gangguan_reports.nama_odp`; cache kabel dibuang hanya saat aman.
- Area divalidasi dengan kode unik; rename memperbarui `kode_area` pada tabel yang memiliki kolom tersebut; jumlah ODP ditampilkan "Tidak tersedia" bila schema live tidak punya `odp.kode_area`.
- ODC kini membaca tabel `odc` dan menampilkan peta/tabel read-only dinamis.
- Customer map memilih kolom minimum, menampilkan assignment ODP dan hitungan mapped/unmapped, memakai Leaflet lokal dan fit bounds.
- Get coordinate memakai Leaflet lokal, center konfigurasi, click/drag/manual validation/copy tanpa ArcGIS key atau jQuery lama.

## File
- `routes/modules/coverage.php`
- `app/Http/Controllers/Admin/CoverageController.php`
- `resources/views/admin/coverage/odp.blade.php`
- `resources/views/admin/coverage/area.blade.php`
- `resources/views/admin/coverage/odc.blade.php`
- `resources/views/admin/coverage/customers.blade.php`
- `resources/views/admin/coverage/get.blade.php`
- `tests/Feature/CoverageFunctionalityTest.php`

## Validasi
- `php artisan test tests/Feature/CoverageFunctionalityTest.php`: 5 PASS, 19 assertions.
- Pint targeted + `pint --test`: PASS, 3 PHP files.
- Tidak ada perubahan schema atau akses database shared live selama test (SQLite in-memory).
