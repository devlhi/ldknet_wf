# Audit lintas modul WhatsApp

- Waktu: 2026-07-16 00:38 WIB
- Agent: Droid
- Scope: webhook legacy/Meta, gateway admin, broadcast, API/resolver/notifier, UI setting, route, dan regression test.

## Bug yang diperbaiki

- Webhook legacy hanya aktif saat provider lama aktif; command sensitif wajib token callback per-install dan nomor admin/developer terdaftar, sementara pesan biasa tetap kompatibel tanpa token selama transisi.
- Auto-grant admin bagi anggota grup dihapus dan log payload/password mentah dimatikan.
- ID layanan/router dari command dinormalkan string-to-string; email penuh tidak lagi ditambah `@gmail.com`; variabel profil PPP diinisialisasi aman.
- Webhook Meta memproses seluruh entry/change/message, memakai lock per Meta Message ID untuk retry konkuren, dan mencatat auto-reply sent/failed berdasarkan respons Graph sebenarnya.
- Test Message admin mendeteksi respons error/null dan mempertahankan input.
- Broadcast memvalidasi pesan/media HTTPS/type, menghitung berhasil/gagal, menangani exception, dan tidak memenuhi Inbox dengan conversation broadcast.
- Nomor tanpa prefix dinormalkan ke `62`; SSL verification gateway lama diaktifkan.
- Deteksi provider memakai konfigurasi URL/blob, bukan nama gateway.
- Tombol hapus gateway yang sebelumnya menuju route hapus layanan diganti route POST khusus gateway.
- App Secret dan Access Token tidak lagi diprefill ke DOM; nilai lama tetap dipertahankan bila field edit kosong. Halaman webhook tidak mengekspos token/blob Meta.
- UI menampilkan callback URL legacy bertoken dan peringatan keras jika App Secret Meta belum diisi.

## Verifikasi

- `php artisan test --filter=WhatsApp`: 21 pass, 106 assertions.
- `php artisan test`: 28 pass, 1 skip, 154 assertions.
- `php vendor/bin/pint --dirty`: pass.
- `php artisan view:cache`: pass.
- `php artisan route:list --path=whatsapp`: route gateway delete dan webhook terdaftar.
- `git diff --check`: pass.

## Catatan deploy

- Gateway aktif saat audit adalah Meta dan App Secret belum dikonfigurasi. Isi App Secret dari menu edit gateway agar signature webhook Meta benar-benar enforced.
- Jika suatu saat gateway lama diaktifkan, ubah callback provider ke URL lengkap bertoken yang ditampilkan pada form setup/edit sebelum memakai command admin.
- Tidak ada perubahan schema DB. File untracked `toArray())` tidak disentuh.
