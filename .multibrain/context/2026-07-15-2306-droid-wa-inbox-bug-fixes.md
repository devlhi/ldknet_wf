# WhatsApp Inbox bug fixes

- Waktu: 2026-07-15 23:06 WIB
- Agent: Droid
- Scope: `app/Http/Controllers/Admin/WhatsAppInboxController.php`, `resources/views/admin/gateway/whatsapp/inbox.blade.php`, `tests/Feature/WhatsAppInboxMediaTest.php`

## Perubahan

- Timestamp hasil polling disamakan menjadi `H:i`.
- Status pesan keluar tanpa webhook delivery/read memakai satu tick.
- Draft teks disimpan per nomor di localStorage, dipertahankan saat submit gagal, dan dibersihkan hanya sesudah server mengonfirmasi kirim teks sukses.
- Polling daftar percakapan tetap berjalan saat belum ada chat dipilih; request overlap dicegah dan message ID dideduplikasi sebelum append.
- Submit guard mencegah polling mengubah thread selama form sedang dikirim.
- Perubahan jendela balas menyimpan draft dan prompt hanya sekali agar teks tidak hilang.
- Kegagalan menyimpan preview lokal gambar keluar dicatat dengan hash Meta ID dan diinformasikan sebagai partial success karena gambar sudah terkirim ke pelanggan.
- Test kirim teks memverifikasi flash `wa_text_sent`.

## Verifikasi

- `php artisan test --filter=WhatsAppInboxMediaTest`: 8 pass, 62 assertions.
- `php artisan test`: 15 pass, 1 skip, 110 assertions. Smoke test dilewati karena membutuhkan DB MySQL `landaknet`.
- `php vendor/bin/pint --dirty`: pass.
- `php artisan view:cache`: pass.
- `git diff --check`: pass.

## Catatan

- Tidak ada perubahan schema DB.
- File untracked `toArray())` tidak disentuh dan tidak boleh ikut commit.
