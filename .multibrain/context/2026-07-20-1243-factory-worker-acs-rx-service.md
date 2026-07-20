# Context: ACS RX service

- **Tanggal**: 2026-07-20 12:43 WIB
- **Agent**: factory-worker
- **Bucket**: features

## Tujuan

Menyediakan integrasi RX Power ACS yang dapat dipakai ulang oleh CoverageController tanpa mengubah CoverageController atau view RX Power.

## Keputusan

`App\\Services\\AcsDeviceService` menerima URL ACS pada `getRxPowerByPppoeUsername(string $acsUrl)` dan mengembalikan `array<string, mixed>` berindeks username PPPoE ternormalisasi (trim + lowercase). Service memanggil `ACSRequest::getAllDevices()` tepat sekali per operasi dan memakai fallback parameter yang sama seperti dashboard ACS: username dari `VirtualParameters.pppoeUsername`, `VirtualParameters.pppUsername`, lalu WAN PPP Username; RX dari `VirtualParameters.RXPower`, `VirtualParameters.redaman`, lalu WAN PON RXPower. `getDashboardDevices()` mengembalikan baris dashboard yang sebelumnya dibentuk controller.

`ACSRequest` sekarang punya `CURLOPT_CONNECTTIMEOUT=5` dan `CURLOPT_TIMEOUT=30` menggantikan timeout infinite. Respons invalid/exception dibungkus RuntimeException oleh service.

## File yang Disentuh

- `app/Services/AcsDeviceService.php`
- `app/Libraries/ACSRequest.php`
- `app/Http/Controllers/Server/AcsController.php`
- `tests/Unit/Services/AcsDeviceServiceTest.php`

## Follow-up

CoverageController dapat memanggil:

```php
$rxPowerData = app(\\App\\Services\\AcsDeviceService::class)->getRxPowerByPppoeUsername($acsUrl);
```

Tangani `RuntimeException` untuk menampilkan fallback kosong/error sesuai UX Coverage. Perubahan CoverageController dan view RX Power sengaja tidak dilakukan oleh worker ini.

Validasi: `php artisan test --filter=AcsDeviceServiceTest` = 3 passed / 12 assertions; Pint pada 4 file = PASS; `git diff --check` = PASS. Working tree juga memuat perubahan Coverage dari agent lain; jangan menimpa atau meresetnya.
