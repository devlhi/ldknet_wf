# LandakNet — Laravel 12 (migrasi dari CI4)

Aplikasi billing/manajemen ISP. Legacy CI4 di `C:\laragon\www\landaknet` masih production; project ini adalah rewrite bertahap ke Laravel 12 dengan **share DB yang sama** (`landaknet` @ localhost, root, no password).

## Wajib baca sebelum kerja

1. `.multibrain/session.md` — master index shared memory antar developer/AI tool. Ikuti skill `multi-brain` (`.claude/skills/multi-brain/SKILL.md`) untuk baca/tulis.
2. `MIGRATION-CONVENTIONS.md` — 12 aturan migrasi (URL identik dengan CI4, tabel legacy `$timestamps=false`, dll.)

## Aturan yang tidak boleh dilanggar

- **Jangan ubah skema DB** — di-share dengan CI4 production.
- **Jangan sentuh** folder legacy CI4 di `C:\laragon\www\landaknet` (dan folder `weblandak/` di sana = CI3, abaikan total).
- Helper lama `encrypt()`/`decrypt()` sudah di-rename `legacy_encrypt()`/`legacy_decrypt()` (bentrok helper Laravel). Password router Mikrotik/OLT di DB pakai ini.
- Flash key untuk error auth = `auth_errors` (bukan `errors`, bentrok `ViewErrorBag`).
- URL path harus **sama persis** dengan CI4 supaya link lama tetap jalan.
- Kode Webhook/Callback payment (`/webhook/*`, `/callback/*`) = uang → port 1:1, jangan ubah logic.

## Struktur route

Route per modul di `routes/modules/*.php`, auto-required dari `routes/web.php`. Tambah modul baru = tambah file di sana, tidak perlu edit `web.php`.

## Alur handoff antar sesi/agent

Selesai kerja → tulis 1 baris di sub-index yang tepat di `.multibrain/indexes/`, update timestamp bucket di `.multibrain/session.md`. Detail panjang (keputusan, file yang disentuh, follow-up) → file baru di `.multibrain/context/`. Lihat template di `.claude/skills/multi-brain/assets/`.
