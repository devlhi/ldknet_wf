# 2026-07-08 00:30 WIB | codebuddy | Port fitur tambahan legacy CI4 & Modul P0 (ACS, Voucher, Callback, Webhook, Auto)

## Ringkasan
Melakukan sinkronisasi fitur baru yang ada di repo CI4 legacy terbaru ke Laravel 12. Semua route dan controller portingan 1:1, tanpa ubah skema DB dan tanpa sentuh project CI4 lama.

## File yang disentuh/dibuat
- **Route baru:** `routes/modules/acs.php`, `routes/modules/voucher.php`, `routes/modules/callback.php`, `routes/modules/webhook.php`, `routes/modules/auto.php`. Total routes = 258.
- **Controller P0 (1:1):** `CallbackController`, `WebhookController`, `AutoController`.
- **Controller P1 (Baru):** `Server\AcsController`, `Server\VoucherController`.
- **Controller P2/P3 Endpoint Tambahan:**
  - `Admin\CustomerController`: `exportCSV`, `exportExcel`, `ambilDataFilterCustomer`, `customerUpdateTikor`, `getPPPOEUsers`, `getODPByArea`.
  - `Admin\CoverageController`: `area`, `areaAdd`, `updateArea`, `deleteArea`, `getCustomerMap`, `rxpower`, `updateODP`, `deleteODP`.
  - `Admin\ServicesController`: `getProfileRouter`.
  - `Admin\FinanceController`: `invoicePrint`, `deleteInvoice`, `bulkDeleteInvoice`.
  - `Admin\GatewayController`: `webhook`, `webhookUpdate`.
  - `Server\RouterController`: `getTrafficFixes`.
- **View Blade:** Menambahkan minimal layout portingan untuk `acs/home`, `acs/dashboard`, `acs/modem`, `voucher/home`, `voucher/report`, `voucher/users`, `voucher/template`, `coverage/area`, `coverage/customers`, `coverage/rxpower`, `admin/webhook/index`.

## Keputusan teknis
- Tabel `area` & kolom `kode_area` di tabel `odp` / `orders` di legacy rupanya tidak didefinisikan di migrasi Laravel. Karena aturan tidak boleh merubah DB & migrasi, maka dibuat safe fallback (`Schema::hasTable` & `Schema::hasColumn`) pada logic map/area di `CoverageController` & `CustomerController` agar halaman tidak 500 error ketika di localhost.
- Wrapper CI4 query builder (seperti `getNumRows`, `getResult`, `getResultArray`) digunakan via trait/compatibility-layer sementara di dalam file controller migrasi P0 besar (`WebhookController` dkk) agar meminimalkan logic rewrite dari 1000+ line.
- Fitur webhook terdeteksi mengambil query dari tabel `whatsapp_setting`, di-bind ke halaman setting admin di `Admin\GatewayController@webhook`.

## Verifikasi
- `php artisan route:list` = 258 routes (bertambah ~51 rute), tidak ada error exception.
- Linter `php -l` seluruh controller bersih dari syntax error.

## Langkah Berikutnya
- Verifikasi testing live flow money/webhook dengan server dev (Callback Tripay/Paydisini, webhook whatsapp, dan cronjobs di AutoController).
- Pastikan library `ACSRequest` portingan dari legacy berjalan sesuai expected request GenieACS saat dicoba dengan alat Mikrotik asli.
- Tinjau ulang schema DB (terutama untuk `area` & voucher) dengan kondisi server production jika ingin generate migration pelengkap.
