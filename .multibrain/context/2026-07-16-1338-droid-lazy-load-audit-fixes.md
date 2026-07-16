# 2026-07-16 13:38 WIB | Droid | Audit & Perbaikan Lazy-Load Sisa Halaman Koleksi

## Ringkasan

Menindaklanjuti audit atas commit `b161202` yang melewatkan 6 halaman bertabel/koleksi data. Semua halaman sisa tersebut kini telah di-lazy-load secara konsisten menggunakan tombol "Tampilkan Data" dan parameter `show_data=1`.

## Hasil audit dan perbaikan

1. **Peta Jaringan (`/admin/coverage/peta`)**
   - ODP dan Cable cache diblokir dari DB pada GET awal.
   - Peta Leaflet tidak diinisialisasi dan antrean routing OSRM tidak dijalankan sampai klik tombol "Tampilkan Data".

2. **Voucher Dashboard (`/server/voucher/dashboard`)**
   - Query ringkasan pendapatan dan data grafik diblokir.
   - Disederhanakan dari 7 query aggregate terpisah menjadi 2 query (1 batch query summary dengan CASE WHEN + 1 chart query) untuk efisiensi saat diaktifkan.

3. **Accounting Dashboard (`/admin/accounting`)**
   - Query 10 jurnal terbaru (`$recentJournals`) dan balance index diblokir.
   - Saldo-saldo nominal hanya dihitung setelah klik.

4. **Template Message Voucher (`/server/voucher/template/message`)**
   - DB query tabel template diblokir dari query awal.

5. **Template Message Admin (`/admin/template/message`)**
   - DB query tabel template diblokir dari query awal.

6. **Generate Invoice (`/admin/finance/invoice/generate`)**
   - Menghapus query `$paket` yang tidak digunakan di view.
   - Mengganti `Order::all()` (loading berat) dengan `Order::get(['idpel', 'nama', 'paket'])` terbatasi yang hanya diload setelah tombol ditekan.

7. **NMS Map Data Endpoint Guard (`/admin/nms/map-data` & `/nms/monitor/data/map`)**
   - Mencegah bypass query langsung dari sisi AJAX sebelum aktivasi.
   - Menambahkan signature parameter `Request $request` pada `publicMapData(Request $request)` untuk mem-forward request dengan benar, menghindari fatal `ArgumentCountError`.
   - NMS map tile Leaflet tidak di-load sebelum aktivasi.

## Test

- Menambahkan test baru `tests/Feature/TableAuditLazyLoadingTest.php` untuk memverifikasi keenam rute tersebut dan NMS public map data.
- Unit/Feature test suite: **48 passed, 414 assertions**.
- Pint formatter: clean.
- Blade compile: pass.
- File untracked `toArray())` tidak disentuh.
