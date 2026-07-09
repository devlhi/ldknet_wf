# Agent Instructions — LandakNet Laravel

Konvensi ini berlaku untuk **semua** AI coding tool (Claude Code, Codex, Cursor, OpenCode, dll.) yang bekerja di repo ini.

## Sebelum menulis kode apa pun

1. Baca `.multibrain/session.md` (< 1 menit, hanya pointer). Pilih bucket yang relevan → baca 1-2 sub-index di `.multibrain/indexes/`. Buka file `.multibrain/context/*.md` hanya kalau pointer menunjukkan detail yang kamu butuhkan.
2. Baca `MIGRATION-CONVENTIONS.md` di root.
3. Baca `CLAUDE.md` (aturan tidak boleh dilanggar).

## Setelah selesai kerja / milestone

Tulis handoff supaya sesi/agent berikutnya tidak mengulang analisis:

1. Buka `.multibrain/indexes/<bucket>.md` yang cocok (buat baru kalau belum ada, pakai `.claude/skills/multi-brain/assets/sub-index-template.md`).
2. Tambah entry **1 baris di paling atas**:
   ```
   - YYYY-MM-DD HH:MM WIB | <nama-agent-atau-tool> | ringkasan singkat -> .multibrain/context/YYYY-MM-DD-HHMM-<agent>-<topik>.md
   ```
3. Kalau ada keputusan/file penting yang layak disimpan detail → buat file di `.multibrain/context/` pakai template.
4. Update baris bucket di `.multibrain/session.md` (timestamp saja).

## Aturan format handoff

- Timestamp zona **WIB**.
- Entry log **wajib 1 baris**; detail panjang → file context.
- Path selalu relatif terhadap root project Laravel.
- Jangan tempel kode/diff panjang di memory — cukup pointer ke file + line.
- Jaga sub-index ≤ 25 entry; kompres entry lama jadi ringkasan, jangan hapus.

## Prinsip umum

- **Tidak ada asumsi**: kalau ragu, cek kode dulu.
- **Root cause > workaround**: kalau tes gagal, cari penyebabnya, jangan skip.
- **Reversible action bebas dilakukan** (edit file, jalankan tes). Destructive action (drop tabel, force push, hapus file besar) → konfirmasi user dulu.
- **Jangan sentuh legacy CI4** di `C:\laragon\www\landaknet`.
