# Production Audit Menu/Route/Controller

Waktu: 2026-07-08 19:31 WIB
Agent: Droid

## Cakupan

Audit diminta user untuk memastikan menu, controller, route, dan view tidak ada yang putus sebelum production.

## Perbaikan dilakukan

- `routes/modules/finance-area.php`
  - Fix URL role finance yang dobel prefix: `finance/finance/kas` -> `finance/kas`.
  - Fix `finance/finance/kas/add` -> `finance/kas/add`.
  - Fix `finance/finance/invoice/detail/{any}` -> `finance/invoice/detail/{any}`.
- `resources/views/admin/finance/invoice/pay/tripay.blade.php`
  - Fix return Tripay dari URL kosong `/invoice` ke `admin/finance/invoice`.
- `resources/views/server/router/ppp/profile.blade.php`
  - Form fitur PPP profile yang memang disabled dinetralkan ke `action="#" onsubmit="return false;"` supaya tidak menunjuk route mati.
- `resources/views/server/router/ppp/secret.blade.php`
  - Form fitur PPP secret yang memang disabled dinetralkan ke `action="#" onsubmit="return false;"`.
- `resources/views/admin/layout.blade.php`
  - Sidebar Coverage dilengkapi: ODC, ODP, Area, Customer Map, RX Power, Get Coordinat Location.
  - Sidebar Server dilengkapi: ACS dan Voucher submenu (Dashboard, Users, Report, Report Orders, Template Message).

## Verifikasi

- `php artisan route:list` sukses, 273 routes.
- `php artisan view:cache` sukses, semua Blade compile.
- `vendor/bin/pint --dirty` sukses, 0 file berubah.
- `vendor/bin/phpunit` sukses: 3 tests, 2 assertions, 1 skipped.
- Grep bug URL lama: tidak ada sisa `url('invoice')`, `ppp/addsecret`, `server/router/ppp/profile/add`, atau `finance/finance`.
- Audit literal `url('...')` di Blade:
  - 78 missing GET awal adalah mayoritas POST form/AJAX.
  - Setelah dibandingkan dengan semua method route, tersisa 6 false positive:
    - `/dashboard` di `welcome.blade.php` adalah default Laravel welcome, bukan flow produksi.
    - `admin/coverage/area/update` dirangkai JS menjadi `admin/coverage/area/update/{id}` dan route POST ada.
    - `admin/whatsapp/setup?provider=meta` route `admin/whatsapp/setup` ada, query string saja.
    - `finance/cash-flows/filter/ambil-data` dirangkai JS dengan `{bulan}/{tahun}` dan route ada.
    - `finance/invoice/filter/ambil-data` dirangkai JS dengan `{bulan}/{tahun}` dan route ada.
    - `data/bukti` adalah file public path `public/data/bukti`, bukan route Laravel.

## Catatan sisa

- Fitur router PPP/hotspot tertentu masih sengaja disabled dengan label `belum tersedia`, bukan link rusak.
- Live verification tetap perlu untuk payment/webhook/router karena butuh kredensial/perangkat eksternal.
