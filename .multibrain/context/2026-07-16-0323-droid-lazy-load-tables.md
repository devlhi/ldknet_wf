# 2026-07-16 03:23 WIB | Droid | Lazy-load semua halaman tabel data

## Ringkasan

Implementasi pola "Tampilkan Data" di **semua** halaman menu-level yang memiliki tabel data. Saat menu dibuka, tabel kosong dan tidak ada query baris / koneksi remote yang dijalankan sampai user klik tombol **Tampilkan Data**. Aktifasi via flag `show_data=1`.

## Pola implementasi

1. Controller: cek `$request->boolean('show_data')` → jika false, kirim `collect()` / empty paginator / empty DataTables JSON; jika true, jalankan query normal.
2. View: tampilkan alert info + tombol "Tampilkan Data" (link `request()->fullUrlWithQuery(['show_data' => 1])`) saat data belum dimuat.
3. DataTables server-side (Customer): endpoint return empty envelope tanpa flag, JS tidak init DataTable sampai klik.
4. Export/CSV endpoint: **tidak** di-gate (tetap query langsung dari filter, bukan dari flag show_data).
5. Form-entry page (bukan tabel): **tidak** di-lazy-load (mis. Add Hotspot User, SLA Global Settings).

## Cakupan (90 file, 22 controller, 68 view, 7 test file baru)

### Priority 0 — Tabel operasional besar
- Data Customer (`/admin/customers`) — DataTables server-side, defer init
- Invoice (`/admin/finance/invoice`) — AJAX DataTables
- Cash Flows (`/admin/finance/cash-flows`)
- Rekap Absensi (`/admin/absensi/rekap`)
- Laporan Gangguan (`/admin/gangguan`)
- Accounting: journals, contacts, products, sales, purchases, expenses, assets, + 5 report pages
- ACS dashboard (`/server/acs/dashboard`)
- Router tables: hotspot users/active/log/host, ppp profile/secret/active
- OLT PON (`/server/olt/pon/{id}`)
- Coverage: ODP, customers, rxpower

### Priority 1 — Tabel operasional sedang
- Finance reports (billing, cash-flow, customers, new-customers) — standardize existing click-lazy
- Voucher reports + users
- NMS index + SLA report
- Karyawan history, User invoice/service

### Priority 2 — Tabel konfigurasi/kecil
- Admin users, coupon, services, payment method, WhatsApp gateway, webhook, cron
- Router/OLT/ACS inventory home + dashboard
- Coverage area, Cash-flow category
- OLT bot WhatsApp

## Perbaikan dari review

- Export CSV absensi: gate dihapus, tetap query langsung dari filter.
- Form Add Hotspot User: lazy-load dihapus, dependencies dimuat normal (bukan tabel data).
- SLA Global Settings: lazy-load dihapus, form device list dimuat normal.
- CronLog heartbeat: dipindah ke setelah `showData` check.
- NMS SLA report: `slaSettings` query ikut di-gate.
- Test keamanan webhook: diperbaiki untuk verifikasi pre+post activation.

## Test

- 7 test file baru (Customer, Finance, Accounting, AdminConfig, AdminRemaining, RemoteOperational, MenuLevel)
- Full suite: **45 passed, 1 skipped, 375 assertions**
- Pint: clean (48 files)
- Blade: compiled successfully

## Catatan

- Tabel detail/document/form line-item tidak di-lazy-load (contextual, bukan menu-level).
- Filter dropdown kecil (tahun, kategori, router list) tetap eager untuk UX.
- `toArray())` untracked tidak disentuh.
