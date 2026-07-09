# wa-gateway — WhatsApp Gateway (lama + Meta Official) & template

Work log, entry 1 baris, terbaru di ATAS.

- 2026-07-08 20:41 WIB | Droid | Sidebar admin: `Chat Whatsapp` dipisah jadi menu utama sendiri (`admin/whatsapp/inbox`), tidak lagi di dalam submenu `Whatsapp Gateway`; submenu gateway tetap Setting, Test Message, Meta Templates. view:cache, Pint, PHPUnit PASS.
- 2026-07-08 20:37 WIB | Droid | Inbox reply signature sekarang bisa dipilih: auto pakai nama admin login atau manual input per pesan. Controller validasi `signature_mode` auto/manual dan `signature_name` wajib saat manual, lalu append `\n\n~{signature}` sebelum kirim Meta. php -l, Pint, PHPUnit, view:cache PASS.
- 2026-07-08 20:14 WIB | Droid | Tambah signature admin otomatis di setiap pesan balasan inbox: pesan keluar otomatis di-append `\n\n~{nama admin}` sebelum dikirim via Meta API dan disimpan ke `wa_inbox_messages` bersama signature. Cek panjang 4000 char setelah signature. Pint, PHPUnit, view:cache PASS.
- 2026-07-08 20:08 WIB | Droid | Audit WA Inbox Meta end-to-end (webhook->store->poll->send). Fix: send reply cek error Graph API dan simpan status gagal sebagai alert (sebelumnya selalu dianggap sukses). Inbox view: tambah banner petunjuk setup kalau Meta gateway OFF atau webhook OFF, tampilkan webhook URL. php -l, route:list, view:cache, Pint, PHPUnit PASS. -> .multibrain/context/2026-07-08-2008-droid-wa-inbox-meta-audit.md
- 2026-07-08 16:35 WIB | kiro | Rebrand semua template WA ke "ANNORTY NET"; `notif_pelanggan_baru` (ditolak Meta krn keyword kredensial) → password dibuang, body netral, di-rename jadi `notif_daftar_berhasil` (6 param). Sesi Meta template **DI-PENDING** atas permintaan user; sisa kerja di sisi Meta Dashboard + fitur edit-template-from-app belum dibuat. -> .multibrain/context/2026-07-08-1635-kiro-wa-meta-template-rename.md
- 2026-07-08 02:30 WIB | claude | 9-router support + WA Inbox send (Meta reply). -> .multibrain/context/2026-07-08-0230-claude-9router-wa-inbox.md
- 2026-07-08 01:40 WIB | codebuddy | Semua setting Meta pindah ke menu WhatsApp Gateway (tanpa .env). -> .multibrain/context/2026-07-08-0140-codebuddy-wa-meta-menu-settings.md
- 2026-07-08 01:25 WIB | codebuddy | Fix switch template lama vs Meta (sendTemplate/sendMessage). -> .multibrain/context/2026-07-08-0125-codebuddy-wa-template-switch-fix.md
- 2026-07-08 01:10 WIB | codebuddy | Port WhatsApp Meta Official (library, resolver, webhook, template manager). -> .multibrain/context/2026-07-08-0110-codebuddy-wa-meta-official.md
- 2026-07-08 00:20 WIB | factory-droid | Audit template WA. -> .multibrain/context/2026-07-08-0020-factory-droid-wa-template-audit.md
