# Peta Jaringan: view area di-set (DB) + basemap pilihan + jalur kabel fiber OSRM beranimasi

**Tanggal:** 2026-07-12 ~22:00 WIB · **Agent:** Claude (Opus)

## Permintaan user
"Buat peta di coverage yang area view-nya bisa di-set (simpan di DB), pilihan tampilan peta (basemap), dan animasi flow kabel fiber ke ODP yang mengikuti jalan."
Keputusan user (via AskUserQuestion): jalur kabel **otomatis ikuti jalan via OSRM**, topologi **titik pusat/OLT → tiap ODP**.

## Yang dibangun (halaman baru `admin/coverage/peta` — menu "Peta Jaringan" di sidebar Coverage)
- **Pengaturan peta tersimpan di DB** (tabel baru `coverage_map_setting`, 1 baris): titik pusat/OLT (hub_label/lat/lng), tampilan default (center_lat/lng + zoom), basemap default. Halaman `admin/coverage/peta/pengaturan` — peta picker (klik = taruh hub 📡), pencarian lokasi Nominatim, tombol "Jadikan tampilan peta saat ini sbg default" (ambil center+zoom), pilih basemap default.
- **Basemap pilihan** (gratis tanpa API key), kontrol layer di peta: Jalan (OSM), Satelit (Esri World Imagery), Topografi (OpenTopoMap), Mode gelap (CARTO dark).
- **Jalur kabel fiber hub→ODP mengikuti jalan**: routing via **OSRM publik** (`router.project-osrm.org`, gratis) dari sisi klien; hasil geometry (banyak titik ikut jalan) **di-cache di DB** (tabel `coverage_cables`, unique per odp_id, + `src_hash` koordinat hub+odp). Load berikutnya pakai cache (instan, tanpa panggil OSRM). Kalau hub dipindah → cache dihapus (routing ulang). Kalau OSRM gagal → fallback garis lurus.
- **Animasi flow**: tiap kabel = garis dasar tebal transparan + garis tipis terang `dashArray` dengan CSS `@keyframes` menggeser `stroke-dashoffset` (efek aliran cahaya hub→ODP). Class `.fiber-flow`.
- Antrean routing berurutan jeda 650ms (hormati batas wajar OSRM) + indikator "menarik jalur kabel...".

## File
- Migrasi: `2026_07_12_000003_create_coverage_map_setting_table.php`, `2026_07_12_000004_create_coverage_cables_table.php`.
- Model: `CoverageMapSetting` (current()/defaults), `CoverageCable` (cast path array).
- Controller `Admin\CoverageController`: `peta()`, `petaSettings()`, `petaSettingsUpdate()` (hapus cache bila hub pindah), `storeCable()` (AJAX cache OSRM).
- Route (`routes/modules/coverage.php`): coverage/peta, coverage/peta/pengaturan (GET/POST), coverage/peta/cable (POST).
- View: `admin/coverage/peta.blade.php`, `admin/coverage/peta-settings.blade.php`.
- Sidebar: "Peta Jaringan" ditambah di submenu Coverage (`layout.blade.php`).

## Verifikasi (browser, live)
Login admin temp → set hub dekat ODP asli (id 36) → buka Peta: hub 📡 + marker ODP + basemap switcher + **kabel ikut jalan (74 titik OSRM, bukan garis lurus)** + animasi flow; cache tersimpan di `coverage_cables`. Reload → kabel muncul instan dari cache (tanpa OSRM). Halaman pengaturan render OK (picker + Nominatim + capture view). Pint clean, migrate OK, view compile OK. Data uji (admin temp, cable, hub) sudah dibersihkan, setting direset default.

## PENTING deploy (landaknet.my.id)
Jalankan **`php artisan migrate`** untuk tabel `coverage_map_setting` & `coverage_cables`. Lalu buka **Coverage → Peta Jaringan → Pengaturan**, set titik pusat/OLT + area tampilan. Jalur kabel butuh ODP punya koordinat (isi via menu ODP). OSRM routing perlu akses internet dari browser admin.
