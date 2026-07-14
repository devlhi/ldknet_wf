# Context: Perkuat nuansa UI WhatsApp Inbox agar lebih mirip WhatsApp asli

- **Tanggal**: 2026-07-14 15:03 WIB
- **Agent**: Droid
- **Bucket**: wa-gateway

## Tujuan

User meminta tampilan inbox WhatsApp dibuat semirip mungkin dengan WhatsApp asli (nuansa, warna, layout).

## Keputusan

Hanya 1 file view berubah: `resources/views/admin/gateway/whatsapp/inbox.blade.php`. Tidak sentuh controller/route/model/polling contract.

Perubahan visual:
- Header sidebar & header chat kini berlatar teal `#008069` (seperti WhatsApp Web), teks putih.
- Search bar percakapan dengan ikon kaca pembesar dan placeholder "Cari atau mulai percakapan baru".
- Avatar bulat dengan gradient hijau `#00a884 -> #008069`.
- Wallpaper thread kini SVG doodle inline (transparan, tidak butuh aset eksternal) di atas canvas `#efeae2`.
- Bubble masuk putih dan bubble keluar hijau `#d9fdd3` dengan ekor CSS (`::before` clip-path).
- Meta pesan `float: right` sejajar dengan teks (seperti WhatsApp), tick `✓✓` untuk terkirim dan `✗` untuk gagal.
- Composer: textarea rounded tanpa border, tombol kirim bulat hijau, auto-grow textarea sampai 120px.
- Waktu pesan di Blade dan polling diubah dari `d M H:i` ke `H:i` (lebih ringkas, mirip WhatsApp).
- Responsive <992px dan <576px dipertahankan, padding thread disesuaikan.

## Kompatibilitas yang DIJAGA

- Semua ID, data-attr, selector, form name, route, dan polling JSON contract tidak berubah.
- `renderMessage()` dan `refreshConversations()` diupdate: `<span class="message-body">` (bukan `<div>`), tick `✓✓`/`✗`, atribut `data-search-title`/`data-search-number` agar hasil polling tetap dapat dicari.
- Search filter (`filterConversations`) hanya menyembunyikan/menampilkan baris di sisi klien; tidak memanggil server.

## Verifikasi

- `php artisan test --filter=WhatsAppInboxMediaTest` -> 8 PASS / 61 assertion.
- `php artisan test` -> 15 PASS / 1 skip (smoke mysql) / 109 assertion.
- `php vendor\bin\pint --dirty` -> 0 issue. `php artisan view:cache` -> OK. `git diff --check` -> bersih.

## Follow-up

- Belum commit/push.
- Belum uji manual lebar layar 320/375/768/991/992/1280/1440.
- File asing untracked `toArray())` (sisa error Tinker) tidak disentuh.
