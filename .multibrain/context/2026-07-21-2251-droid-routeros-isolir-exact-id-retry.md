# Context: Perbaikan retry isolir RouterOS dengan exact ID

- **Tanggal**: 2026-07-21 22:51 WIB
- **Agent**: Droid
- **Bucket**: modules-parallel

## Tujuan

Menghilangkan kegagalan berulang `no such item` pada cron isolir tanpa mengubah skema DB, sekaligus menjaga retry aman untuk kegagalan router dan pemulihan akses setelah pembayaran.

## Keputusan

`AutoController::isolir()` kini mencari PPPoE secret atau user Hotspot berdasarkan nama persis, memvalidasi respons trap/fatal, lalu mengubah profil menggunakan `.id` RouterOS. Lookup sesi aktif juga divalidasi; hanya race `no such item` saat sesi sudah hilang sebelum perintah remove yang dianggap idempotent. Akun yang memang tidak ada untuk pelanggan kedaluwarsa dianggap sudah tidak memiliki akses dan tidak lagi menghasilkan warning berulang, sedangkan akun yang hilang pada pemulihan pelanggan berbayar tetap berstatus `Isolir` agar dicoba lagi dan tidak salah menjadi `Active`.

## File yang Disentuh

- `app/Http/Controllers/AutoController.php`: exact lookup, update by `.id`, validasi respons query, semantik missing-account, dan penanganan race remove sesi.
- `tests/Feature/AutoControllerIsolirTest.php`: cakupan PPPoE/Hotspot exact ID, koneksi gagal lalu retry, missing expired/paid, restore Hotspot, dan trap lookup sesi.

## Validasi

Focused test 6 PASS/51 assertions. Full suite 74 PASS/2 skip/427 assertions. Pint, `git diff --check`, dan review spesialis tanpa temuan blocker/high/medium semuanya lulus.

## Follow-up

Atas permintaan user, `tests/Feature/AutoControllerIsolirTest.php` dihapus setelah validasi focused selesai. Suite tersisa setelah penghapusan: 68 PASS/2 skip/376 assertions. Verifikasi live pada satu pelanggan PPPoE dan satu Hotspot ketika router produksi tersedia.
