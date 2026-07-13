# Context: Redesign UI WhatsApp Inbox mirip WhatsApp Web

- **Tanggal**: 2026-07-13 22:21 WIB
- **Agent**: Droid
- **Bucket**: wa-gateway

## Tujuan

User minta tampilan halaman Chat WhatsApp (`admin/whatsapp/inbox`) dibuat mirip UI WhatsApp asli/WhatsApp Web tanpa merusak fungsi Meta (webhook, polling, quoted reply, kirim teks/gambar, jendela 24 jam).

## Keputusan

- Redesign hanya di 1 file view: `resources/views/admin/gateway/whatsapp/inbox.blade.php`. Tidak sentuh controller/route/model/JS contract.
- Pakai CSS page-local + aset yang sudah ada (Bootstrap 5.1.3 + Unicons). TIDAK tambah npm/CDN.
- Palet WhatsApp: accent `#008069`, bubble keluar `#d9fdd3`, canvas `#efeae2`, bubble gagal `#ffe1e1`. Tick hanya `✓ Terkirim` / `✗ Gagal` (tidak memalsukan double-tick read karena backend tak punya datanya).
- Layout flex shell `.wa-inbox` (sidebar 340px + chat pane). Responsive <992px: list-only vs chat-only via class `has-selection` + tombol back ke `admin/whatsapp/inbox` (navigasi tetap lewat `?number=` URL, bukan client routing).
- Avatar = inisial CSS (bukan aset logo WhatsApp), aman hak cipta.
- Aksesibilitas: `role="status"`+`aria-live` di conn/window, `role="log"` di thread, `aria-current="page"` di percakapan aktif, `prefers-reduced-motion` matikan animasi dot.

## Kompatibilitas yang DIJAGA (kritis)

- ID: metaConnStatus, metaConnDot, metaConnText, conversationList, windowStatus, chatThread, replyBox, replyForm, replyToMessageId, replyPreview, replyPreviewText, cancelReplyBtn, sigAuto, sigManual, signatureNameInput.
- Data attr: `#chatThread[data-number]`, `[data-last-id]`, `[data-message-id]`, `[data-conversation-number]`, `.reply-message-btn[data-message-id]`.
- Selector JS: `.message-body`, `.reply-message-btn`, `#replyForm textarea[name="message"]`.
- Nama field form: number, message, reply_to_message_id, signature_mode, signature_name, image, caption.
- Route/method tidak berubah: GET inbox, GET poll, GET media/{message}, POST send, POST send-image.
- `renderMessage()` + `refreshConversations()` di JS diupdate memakai class BARU (`wa-message-row`, `wa-bubble`, `wa-conversation`, dll.) supaya DOM hasil polling identik dengan render awal Blade.

## File yang Disentuh

- `resources/views/admin/gateway/whatsapp/inbox.blade.php` (378+ / 179- ; satu-satunya file berubah).

## Verifikasi

- `php artisan test --filter=WhatsAppInboxMediaTest` → 8 PASS / 61 assertion.
- `php artisan test` → 11 PASS / 1 skip (smoke mysql) / 76 assertion.
- `php vendor\bin\pint --dirty` → 0 issue. `php artisan view:cache` → OK. `git diff --check` → bersih.

## Follow-up

- Belum commit/push (menunggu instruksi user).
- Belum diuji live Meta dengan pesan/gambar pelanggan baru & belum uji manual lebar layar 320/375/768/991/992/1280/1440.
- Opsi lanjutan: restore draft/quote setelah redirect gagal, cegah polling overlap, persist relasi quoted (butuh sidecar/schema baru).
