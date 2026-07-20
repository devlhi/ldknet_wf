# Context: Identitas customer, menu aktif, dan port ODP

- **Tanggal**: 2026-07-20 11:29 WIB
- **Agent**: Droid
- **Bucket**: features

## Tujuan

Memperbaiki highlight sidebar yang salah, login customer yang bisa tertaut ke order pelanggan lain, dan update ODP/port yang dulu belum memvalidasi kapasitas atau okupansi.

## Keputusan

- Sidebar tidak lagi mewajibkan URL persis sama; `app.js` memakai prefix path terpanjang dan link bisa menimpa prefix via `data-active-prefix` (Customers mencakup route singular `/admin/customer/*`).
- Login customer kini menentukan identitas secara tegas: input berformat email hanya dicari di `users.email`, selain itu di `users.nomor`; akun duplikat atau kandidat order dengan `idpel` ambigu ditolak, tidak lagi mengambil baris pertama secara acak.
- Tombol "Ganti Password" customer hanya aktif jika order cocok **email + nomor** dengan akun `users.level=user`; join nama ditinggalkan karena nama bisa sama.
- `customerUpdateODP` memvalidasi ODP ada, rentang port 1..kapasitas, dan okupansi dalam transaksi `SELECT ... FOR UPDATE`; nilai port legacy seperti `03` dinormalisasi numerik di server dan JS.

## File yang Disentuh

- `public/assets/js/app.js` (matcher menu aktif prefix terpanjang)
- `resources/views/admin/layout.blade.php` (atribut `data-active-prefix`)
- `app/Http/Controllers/AuthController.php` (`findLoginUser`, `findCustomerOrder`)
- `app/Http/Controllers/User/UserController.php` (session `idpel` saja)
- `app/Http/Controllers/Admin/CustomerController.php` (join akun aman, reset password dibatasi level user, ODP transactional validation)
- `resources/views/admin/customer/edit.blade.php` (select ODP dedup, port current terpilih, port terisi dinonaktifkan)
- `tests/Feature/AuthCustomerIdentityTest.php` (baru)
- `tests/Feature/CustomerLazyLoadingTest.php` (ODP tests + skema test)

## Follow-up

- P0 lanjutan: validasi amount payment callback terhadap invoice/order, dan state machine provisioning atomik.
- Hardening opsional: biodata-update masih bisa ambigu pada data kotor; `getUsedPorts` hanya menampilkan satu penghuni per port ketika data legacy sudah dobel.
- Full suite saat commit: 56 passed, 449 assertions, 1 skipped.
