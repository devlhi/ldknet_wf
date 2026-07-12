# Laporan Gangguan: Auto-reply + SLA + Deteksi Massal per ODP + Peta ODP lengkap

**Tanggal:** 2026-07-12 ~20:50 WIB · **Agent:** Claude (Opus)

## Konteks
Lanjutan fitur **Laporan Gangguan** (pickup keluhan otomatis dari chat WA). User minta:
"kerjakan (fitur auto-reply + SLA + deteksi gangguan massal), kecuali backup DB, dan pastikan maps ODP juga lengkap."

## Yang dikerjakan

### 1. Balasan otomatis (auto-reply)
- Saat `GangguanReport::capture()` membuat laporan BARU, sistem langsung membalas pelanggan via **gateway aktif** (`WhatsAppNotifier::sendText()` — Meta/lama, gateway-agnostic). Pelanggan baru saja chat → masih dalam window 24 jam Meta (pesan sesi bebas).
- Dibungkus try/catch sendiri: gagal kirim TIDAK menggagalkan pencatatan laporan. Kolom `auto_reply_sent` menandai terkirim.
- Teks & on/off dapat diatur di menu Pengaturan. Variabel: `{nama}`, `{kategori}`.

### 2. SLA penanganan
- Kolom baru `responded_at` (diisi saat status keluar dari 'baru' pertama kali, di `updateStatus`).
- Halaman index menampilkan **rata-rata waktu respons** & **waktu penyelesaian** bulan ini (`AVG(TIMESTAMPDIFF(MINUTE, ...))`).
- Laporan status 'baru' yang lewat batas jam (`sla_response_hours`, default 3) ditandai badge **"telat"** + baris merah + counter di kartu total.

### 3. Deteksi gangguan massal per ODP
- Kolom baru `nama_odp`, `kode_area` di `gangguan_reports` (di-resolve dari `orders` saat capture).
- `GangguanReport::massalAlerts($threshold,$windowHours)` — kelompokkan laporan terbuka (baru/diproses) per ODP dalam rentang jam; ODP dengan >= threshold ditandai gangguan massal. Join `odp` untuk lat/lng + hitung pelanggan aktif.
- Banner peringatan di halaman index + tombol **"Info ke pelanggan"** = broadcast WA ke seluruh pelanggan aktif ODP terdampak (`broadcastOdp`, dipicu manual admin, ada modal konfirmasi). Variabel broadcast: `{odp}`, `{nama}`.

### 4. Peta ODP "lengkap" (`admin/coverage/odp`)
- **Ganti basemap ArcGIS (API key hardcoded + CDN esri) → Leaflet + OSM lokal** (`public/leaflet/`), andal tanpa API key. Fit bounds ke marker ODP (bukan hardcode Jakarta).
- Marker `circleMarker`: **biru = normal, merah = ada laporan gangguan terbuka**. Popup: nama, kode, port terpakai/total, jumlah pelanggan, indikator gangguan.
- Form Tambah/Edit (satu modal reusable) kini punya **map picker + pencarian nama lokasi Nominatim** + lat/lng manual sinkron (pola sama dgn Pengaturan Radius Absen). Tambah field **Kode ODP** (kolom `odp.kode` sebelumnya tak ada di form).
- Tabel ODP kini ada tombol **Edit & Hapus** + kolom Pelanggan, Koordinat (ada/belum), Status gangguan.
- `CoverageController::odp()` hitung pelanggan & gangguan terbuka per ODP sekali (tanpa query di loop). `addodp`/`updateODP` handle `kode`.

## File
- Migrasi baru: `2026_07_12_000001_extend_gangguan_reports_sla_odp.php` (+nama_odp,+kode_area,+responded_at,+auto_reply_sent,index nama_odp), `2026_07_12_000002_create_gangguan_setting_table.php`.
- Model: `GangguanSetting` (baru), `GangguanReport` (capture+auto-reply, massalAlerts, humanDuration).
- Controller: `Admin\GangguanController` (SLA/massal/settings/broadcastOdp), `Admin\CoverageController` (odp enrich + kode).
- View: `admin/gangguan/index.blade.php` (banner massal, SLA, overdue), `admin/gangguan/settings.blade.php` (baru), `admin/coverage/odp.blade.php` (rework peta lengkap).
- Route: `routes/modules/gangguan.php` (+pengaturan GET/POST, +broadcast-odp).

## Verifikasi
Pint clean, `view:cache` OK, migrate OK. Uji live DB: massalAlerts (3 laporan ODP sama → 1 alert), humanDuration, capture+classify+dedup, SLA avg query. Uji browser (login admin temp, dibersihkan): halaman gangguan (banner massal, SLA respons 20m/selesai 1j35m, badge telat), settings, peta ODP (Leaflet+OSM 15 tiles, marker, modal edit isi field + picker Nominatim). Semua data uji & user temp sudah dihapus.

## Addendum (~21:00 WIB): SLA periode + export PDF + riwayat
- **Periode fleksibel** di halaman gangguan: **harian / mingguan / bulanan / tahunan** (dropdown + tanggal acuan). Semua rekap + SLA + daftar mengikuti rentang periode. `GangguanController::resolvePeriode()` hitung [start,end] via Carbon (mingguan = Senin–Minggu, dst).
- **Riwayat SLA per sub-periode** (`slaBreakdown()`): tabel tren — harian→per jam, mingguan/bulanan→per hari, tahunan→per bulan. Kolom: jumlah, selesai, avg respons, avg penyelesaian (`DATE_FORMAT` group + `AVG(TIMESTAMPDIFF)`).
- **Export PDF**: tombol "Export PDF" → halaman cetak standalone `admin/gangguan/cetak` (route baru), A4, `window.print()` (pola sama dgn laporan accounting — TANPA lib PDF, aman di cPanel). Isi: kop perusahaan, ringkasan, rekap kategori, riwayat SLA per sub-periode, **riwayat laporan detail** (dibatasi 1000 baris, ada catatan bila terpotong). View: `resources/views/admin/gangguan/cetak.blade.php`.
- **Riwayat laporan**: laporan tersimpan permanen di `gangguan_reports`; kini bisa ditelusuri per periode apa pun + ikut di PDF (kolom waktu respons & penyelesaian per laporan).
- Verifikasi browser: 4 periode diuji (harian per-jam, mingguan Sen–Min per-hari, tahunan per-bulan April/Jun/Jul), PDF render lengkap tanpa error console. Data uji + admin temp dihapus.

## PENTING untuk deploy produksi (landaknet.my.id)
Jalankan **`php artisan migrate`** di server untuk tabel/kolom baru (`gangguan_setting`, kolom SLA/ODP di `gangguan_reports`). Auto-reply akan BENAR-BENAR mengirim WA (gateway aktif = Meta Official) begitu ada keluhan masuk — pastikan teks di menu Pengaturan sudah sesuai.
