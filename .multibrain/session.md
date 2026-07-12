# LandakNet — Migrasi CI4 → Laravel 12 (Master Index)

> Shared memory antar developer/AI tool. Baca file ini dulu sebelum kerja.
> Update bagian Status/Terakhir Dikerjakan/Langkah Berikutnya setiap selesai sesi kerja.

## Project

- **Aplikasi**: billing/manajemen ISP (pelanggan, Mikrotik, OLT, invoice, payment gateway, WhatsApp)
- **Lama (CI4 4.3.6)**: `C:\laragon\www\landaknet` — masih production, JANGAN diubah
- **Baru (Laravel 12)**: `C:\laragon\www\landaknet-laravel`
- **Database**: MySQL `landaknet` (localhost, root, no password) — **DI-SHARE kedua aplikasi, jangan ubah skema**
- **Konvensi migrasi**: WAJIB baca `MIGRATION-CONVENTIONS.md` di root project Laravel

## Status Terkini (2026-07-08)

Selesai & terverifikasi:
- Skeleton Laravel 12.62 + koneksi DB + assets template (public/assets, 59MB)
- 26 migration (generated dari DB) + 26 Eloquent model (`app/Models`, semua `$timestamps = false`)
- Modul **Auth** lengkap: login email/nomor HP, forgot/reset password (Brevo), middleware `level:`, dites end-to-end
- **Layout admin** (sidebar+topbar) di `resources/views/admin/layout.blade.php` + **dashboard admin** (`Admin\AdminController`)
- 9 library CI4 di-port ke `app/Libraries`, helpers global di-autoload composer
- Modul existing: Customers+Broadcast, Services+Coverage, Finance admin, Settings+Manage+Account, Gateway+Template, Server OLT+Mikrotik
- Modul tambahan: Finance area, User area, Guest tagihan, Server ACS, Server Voucher, AJAX endpoint tambahan.
- P0 money/cron: Callback payment (`/callback/*`), Webhook WhatsApp (`/webhook/*`), AutoController cron (`/auto/*`) di-port 1:1.
- Audit dan perbaikan bug telah dijalankan: perbaikan route order di `customers.php`, fallback `Schema::hasTable` untuk env test.
- **WhatsApp Meta Official**: library `WhatsAppMetaApi`, resolver switch gateway (lama vs Meta) tanpa ubah skema, webhook verify+receiver `/webhook/whatsapp/meta`, admin gateway setting provider dropdown, template manager + test send. Semua setting Meta (Graph URL, WABA ID, verify token, bahasa, nama template) kini dari menu WhatsApp Gateway, tidak perlu `.env`. Template notifikasi (tagihan/pengingat/terbayar/pelanggan_baru) otomatis pakai `sendTemplate()` saat Meta aktif dan `sendMessage()` saat gateway lama aktif. Gateway aktif tunggal via `mode=on`.
- `php artisan route:list` = 285 routes, Pint clean, Test PASS.
- **NMS Monitor**: fitur baru modul NMS (`admin/nms/*`). Tabel baru `nms_devices` + `nms_metrics` (tidak sentuh tabel CI4). Controller `Admin\NmsController` dengan device CRUD, peta Leaflet, real-time polling SFP/power via Mikrotik API atau SNMP, grafik historis ApexCharts. View: index (peta+daftar device), form (add/edit dengan map picker), detail (port monitor+chart). Sidebar menu "NMS Monitor" di section Server Menu. SweetAlert2 untuk konfirmasi hapus.
- Audit production menu/route/controller selesai: fix dobel prefix finance-area, return URL Tripay, form PPP disabled, sidebar Coverage/ACS/Voucher dilengkapi. Tidak ada link rusak tersisa. -> .multibrain/context/2026-07-08-1931-droid-production-audit.md
- Fix logic pembayaran invoice manual: counter Paid/Unpaid casing benar; DB invoice/order/report diupdate dulu sebelum email Brevo, WA terbayar dikirim setelah DB sukses, email gagal hanya log warning dan pembayaran tetap sukses; path Isolir PPPoE/Hotspot kini insert Report pemasukan. -> .multibrain/context/2026-07-08-1948-droid-invoice-payment-logic-fix.md

- **Meta Phone Number ID panjang (16+ digit)**: kolom `whatsapp_setting.sender` legacy = varchar(15), tidak muat. Solusi tanpa ubah skema: Phone Number ID disimpan di blob Meta (`api_url`, field `phone_number_id` di indeks 8 compact `meta###`), kolom `sender` diisi penanda pendek `"meta"`. Resolver baru `WhatsAppGatewayResolver::sender($gateway)` dipakai di semua titik kirim (WhatsAppNotifier, GatewayController::whatsappMetaTest, WhatsAppInboxController::send, WebhookController Meta reply). Form add/edit tetap 1 kolom "Phone Number ID" yang otomatis di-mirror ke hidden `meta_phone_number_id`. Pint clean, AdminSmokeTest PASS.

Sedang berjalan / belum selesai:
- Verifikasi live router/payment untuk module P0.
- Live test WA Meta dengan kredensial asli (webhook HTTPS, send, templates).

## Keputusan Teknis Penting

- Flash error pakai key `auth_errors` (BUKAN `errors` — bentrok ViewErrorBag Laravel)
- Helper lama `encrypt()`/`decrypt()` di-rename `legacy_encrypt()`/`legacy_decrypt()` (bentrok helper Laravel); dipakai untuk password router Mikrotik/OLT di DB
- Session driver = file, queue = sync
- URL path Laravel dibuat SAMA PERSIS dengan CI4 (link sidebar & redirect lama tetap jalan)
- Folder `weblandak/` di project lama = CI3 legacy, DIABAIKAN

## Langkah Berikutnya

1. Verifikasi live flow payment/webhook/cron: Tripay/Paydisini callback, WhatsApp webhook bot, AutoController isolir/cetak invoice/reminder dengan router dev.
2. Verifikasi browser login test untuk seluruh URL sidebar, ACS, Voucher, Coverage area/customer/rxpower, dan guest cek/tagihan.
3. Tinjau schema DB production untuk tabel/kolom baru legacy (`area`, `kode_area`, voucher/ACS) tanpa mengubah skema dari Laravel.
4. Belum ada git repo di project Laravel — pertimbangkan `git init` + commit awal

## Index Topik

- `auth` — login/forgot/reset + middleware role — update: 2026-07-10 WIB (security: reset password, throttle, anti-enumerasi) -> .multibrain/indexes/auth.md
- `admin-dashboard` — layout admin + halaman dashboard — update: 2026-07-10 WIB (fix Pelanggan Baru: baca dari orders bukan psb) -> .multibrain/indexes/admin-dashboard.md
- `modules-parallel` — migrasi 6 modul via sub-agent paralel — update: 2026-07-12 17:40 WIB (retry isolir Mikrotik) -> .multibrain/indexes/modules-parallel.md
- `wa-gateway` — WhatsApp gateway (lama + Meta Official) & template — update: 2026-07-12 18:40 WIB (admin kirim image dalam jendela Meta 24 jam) -> .multibrain/indexes/wa-gateway.md
