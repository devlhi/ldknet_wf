# Temuan audit: `verifyCallback($verify)` di callback pembayaran (Tripay & Duitku)

**Tanggal:** 2026-07-10 · **Oleh:** claude-fable-5 · **Status:** DILAPORKAN, belum diubah (butuh keputusan + live test)

## Ringkas

`CallbackController::tripayCallback` dan `duitkuCallback` memanggil:

```php
$tripay->verifyCallback($provider->callback);   // CallbackController.php:46
$duitku->verifyCallback($provider->callback);   // CallbackController.php:145
```

Signature method-nya `verifyCallback($verify = 0)` lalu `$verify = (int) $verify;`.
Yang dikirim adalah **URL callback** (`$provider->callback`, mis. `https://...`), dan
`(int) "https://..." === 0`. Jadi blok `if ($verify == 1) { ... }` — yaitu
**re-konfirmasi ke server gateway** (`getTransaction`/`transactionStatus`, cek ulang
`amount`/`status`) — **tidak pernah jalan**. Yang aktif hanya verifikasi signature.

## Kenapa TIDAK langsung "diperbaiki" jadi `verifyCallback(1)`

1. **Tripay = port 1:1 dari CI4 production.** Legacy `C:\laragon\www\landaknet\app\Controllers\base\CallbackController.php:84` juga memanggil `verifyCallback($callback)` dengan URL, dan body library-nya identik. CLAUDE.md: kode callback pembayaran = uang, **port 1:1, jangan ubah logic**. Mengubah ke `1` = divergen dari production + mengaktifkan pemanggilan API gateway yang belum teruji di jalur ini (risiko menolak pembayaran sah).
2. **Duitku = kode baru** (tidak ada di CI4), jadi secara aturan boleh diubah, TAPI mengaktifkan `getTransaction` double-check tetap mengubah perilaku jalur uang dan **butuh live test** (sandbox/production) sebelum diaktifkan.
3. Signature HMAC/MD5 (pakai private key/api key) **sudah diverifikasi** di kedua callback → ini pertahanan utama dan tetap jalan. Yang hilang hanya lapis kedua (defense-in-depth), bukan pintu terbuka.

## Rekomendasi (untuk manusia)

- Putuskan apakah lapis kedua mau diaktifkan. Jika ya:
  - **Duitku:** ubah ke `verifyCallback(1)` lalu live-test callback nyata (pastikan `getTransaction($merchantOrderId)` mengembalikan `merchantOrderId` + `statusCode` yang cocok).
  - **Tripay:** ubah **berbarengan** di CI4 + Laravel (jaga 1:1), atau sepakati Laravel boleh menyimpang. Live-test callback Tripay nyata.
- Sudah dikerjakan tanpa mengubah logic: `hash_equals` untuk perbandingan signature Duitku (`DuitkuPayment::verifyCallback`). Tripay dibiarkan `===` agar tetap identik dengan CI4 (kandidat perbaikan terkoordinasi berikutnya).

## File terkait

- `app/Http/Controllers/CallbackController.php` (:46 Tripay, :145 Duitku)
- `app/Libraries/TripayPayment.php::verifyCallback` (identik CI4)
- `app/Libraries/DuitkuPayment.php::verifyCallback` (baru; sudah pakai `hash_equals`)
