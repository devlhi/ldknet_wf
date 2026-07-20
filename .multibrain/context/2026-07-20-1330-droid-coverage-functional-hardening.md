# Coverage functional hardening

- Agent: Droid
- Timestamp: 2026-07-20 13:30 WIB
- Scope: seluruh submenu Coverage dan integrasi ACS RX Power.

## Hasil
- ODC memakai data tabel live; ODP dan Area memiliki validasi, propagasi rename, statistik referensi, dan delete POST+CSRF yang aman.
- Peta Jaringan memvalidasi koordinat/hash cache, menangani kegagalan OSRM/cache, dan tetap bekerja saat tabel cache belum tersedia.
- Customer Map dan Get Coordinate memakai Leaflet lokal, validasi koordinat, bounds dinamis, payload minimum, dan tanpa API key tertanam.
- RX Power membaca ACS nyata melalui `AcsDeviceService`, mencocokkan username PPPoE ternormalisasi, menampilkan status error eksplisit, dan memakai timeout terbatas.
- Tidak ada perubahan schema database shared.

## File utama
- `app/Http/Controllers/Admin/CoverageController.php`
- `app/Http/Controllers/Server/AcsController.php`
- `app/Libraries/ACSRequest.php`
- `app/Services/AcsDeviceService.php`
- `routes/modules/coverage.php`
- `resources/views/admin/coverage/*.blade.php`
- `tests/Feature/CoverageFunctionalityTest.php`
- `tests/Unit/Services/AcsDeviceServiceTest.php`

## Validasi
- Schema live ODC/ODP/orders diperiksa read-only; kolom ODC sesuai implementasi.
- Pint dirty: PASS.
- Blade clear/cache: PASS.
- Coverage JavaScript syntax: PASS.
- Full suite: 61 PASS, 1 skipped, 380 assertions.
- `git diff --check`: PASS.

## Dependency runtime
- RX Power memerlukan record ACS aktif/terkonfigurasi dan ACS yang dapat dijangkau.
- Basemap/routing memerlukan akses outbound ke provider tile dan OSRM; fallback garis lurus digunakan ketika OSRM gagal.
