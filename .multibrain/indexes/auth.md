# Sub-Index: `auth`

Login, forgot/reset password, middleware role.

## Rules

- Entry baru di ATAS.
- 1 baris per entry. Detail panjang → `.multibrain/context/`.

## Entries

- 2026-07-10 WIB (lanjutan) | claude-fable-5 | SECURITY lanjutan. (1) Enumerasi akun ditutup: `auth()` cek password DULU + Hash::check ke hash dummy saat user tak ada (normalisasi timing); pesan no-user & wrong-password diseragamkan → `Email atau password salah`. Pesan `belum diverifikasi`/`nonaktif` hanya tampil SETELAH password benar (bukan sinyal enumerasi). Forgot: balasan generik `Jika email terdaftar...`. (2) `hash_equals` di `DuitkuPayment::verifyCallback` (Duitku = kode BARU, bukan port CI4). (3) Webhook Meta: validasi `X-Hub-Signature-256` opt-in via `config services.whatsapp_meta.app_secret` (env `META_APP_SECRET`), fail-open bila kosong; helper `verifyMetaSignature()` di WebhookController. Verifikasi: temp Feature test vs mysql 6/6 PASS (login valid; generic no-user/wrong-pass identik; unverified tetap spesifik; webhook tolak sig salah=403, terima sig benar), test dihapus setelah lulus; `php artisan test` PASS, Pint clean. TIDAK diubah (sengaja): `TripayPayment::verifyCallback` = port 1:1 IDENTIK CI4 legacy → jalur uang, jangan divergen; arg URL ke `verifyCallback($verify)` (Tripay & Duitku) yang mematikan double-check ke gateway → butuh live test + koordinasi CI4. -> .multibrain/context/2026-07-10-callback-verify-finding.md
- 2026-07-10 WIB | claude-fable-5 | SECURITY FIX reset password: token md5(email+waktu) yang bisa ditebak → `Str::random(64)`, disimpan sebagai sha256 hash di kolom `token` (varchar 255, cukup), TTL 60 menit (cek `date_create`), sekali-pakai (delete semua token email setelah sukses), token lama dibuang saat request baru. Helper `hashResetToken()`/`findValidResetToken()` di AuthController. Throttle ditambah: `auth`=10/1, `sendforgot`=5/10, `reset-password/update`=10/10. Verifikasi live DB 7/7 OK, test suite PASS.
- 2026-07-07 14:00 WIB | claude-fable-5 | Modul Auth selesai + dites (login, wrong password, guest redirect, level restriction, logout, reset password token). Flash key = `auth_errors`. Middleware alias `level:` didaftar di bootstrap/app.php.
