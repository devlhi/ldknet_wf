# Dukungan Alih Mode Paket Customer (PPPoE <-> Hotspot)

- Agent: Droid
- Timestamp: 2026-07-21 10:30 WIB
- Scope: `CustomerController::customerUpdateData()`, alih mode paket customer (`pppoe` <-> `hotspot`), dan tests.

## Root cause & Kebutuhan
- Sebelumnya, perpindahan paket hanya menangani perubahan profil di mode yang sama (default `pppoe` atau `hotspot`). Jika pelanggan berpindah paket dari mode PPPoE ke Hotspot atau sebaliknya (Hotspot ke PPPoE), user/secret lama masih tertinggal dan user baru tidak terkonfigurasi di modul Mikrotik yang tepat.

## Perbaikan
- Memperbarui `CustomerController::customerUpdateData()`:
  1. Mengambil password user lama dari Mikrotik sebelum alih mode dilakukan, sehingga password akun pelanggan tetap sama saat berpindah mode.
  2. Jika terjadi perpindahan mode (`oldMode !== newMode`):
     - Menghapus secret/user lama dan session aktif dari modul lama Mikrotik (misal `/ppp/secret/remove` & `/ppp/active/remove` atau `/ip/hotspot/user/remove` & `/ip/hotspot/active/remove`).
     - Mendaftarkan user baru pada modul target di Mikrotik (`/ip/hotspot/user/add` atau `/ppp/secret/add`) dengan profil paket baru dan status yang sesuai.
  3. Jika mode sama (`oldMode === newMode`):
     - Memperbarui profil paket yang ada via `/ip/hotspot/user/set` atau `/ppp/secret/set`.
     - Jika user belum ada di Mikrotik, otomatis dibuatkan user baru di modul terkait tanpa gagal.
  4. Menyimpan perubahan `mode` dan `paket` di tabel `orders` serta status akun di tabel `users`.
- Menambahkan 2 unit/feature tests di `CustomerLazyLoadingTest.php`:
  - `test_customer_update_data_switches_pppoe_to_hotspot`
  - `test_customer_update_data_switches_hotspot_to_pppoe`

## Validasi
- Targeted tests PASS: `CustomerLazyLoadingTest` (15 passed, 68 assertions).
- Full test suite PASS: 83 passed, 460 assertions, 1 skipped.
