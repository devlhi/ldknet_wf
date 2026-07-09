---
name: multi-brain
description: Shared project memory di dalam repo (.multibrain/) supaya developer/AI tool mana pun (Claude Code, Codex, Cursor, OpenCode) bisa melanjutkan pekerjaan tanpa membaca ulang seluruh history. Baca .multibrain/session.md di AWAL sesi kerja pada project ini, dan tulis entry di AKHIR sesi atau setelah menyelesaikan milestone.
---

# Multi Brain — Shared Memory Antar Agent/Developer

Memory project disimpan di `C:\laragon\www\landaknet-laravel\.multibrain\` (ikut project Laravel, ter-commit ke git).

Model 3 lapis (layered indexing) — jangan baca semuanya, ikuti pointer:

```
.multibrain/
├── session.md          ← master index: HANYA daftar bucket + pointer (index of indexes)
├── indexes/<bucket>.md ← work log per topik, entry 1 baris, terbaru di atas
└── context/<file>.md   ← detail handoff/investigasi, dibuka hanya kalau pointer menunjuk ke sana
```

## Alur kerja

1. **Awal sesi**: baca `session.md` (harus bisa dipindai < 1 menit). Pilih bucket yang relevan dengan tugasmu, baca 1-2 sub-index itu saja. Buka file context hanya jika pointer-nya penting.
2. **Selesai kerja**: tulis entry 1 baris di bucket yang tepat (buat bucket baru kalau belum ada), format:
   ```
   - YYYY-MM-DD HH:MM WIB | <nama-agent> | <ringkasan singkat> -> .multibrain/context/<file>.md (pointer opsional)
   ```
   Entry terbaru di ATAS. Lalu update baris bucket tsb di `session.md` (timestamp-nya).
3. **File context** hanya dibuat kalau detailnya layak disimpan (keputusan, file yang disentuh, follow-up). Nama file: `YYYY-MM-DD-HHMM-<agent>-<topik>.md`.
4. **Jaga ukuran**: maks ~25 entry per bucket — kompres entry lama jadi ringkasan, jangan hapus history.

## Aturan format

- Markdown saja, path relatif terhadap root project Laravel.
- Entry log wajib 1 baris; detail panjang → file context.
- Keputusan teknis penting (kenapa X bukan Y) ditulis di bucket topiknya, bukan di session.md.
- Jangan tulis kode/diff panjang di memory — cukup pointer ke file & baris.
- Timestamp zona WIB.

## Template

Lihat folder `assets/` di skill ini: `session-template.md`, `sub-index-template.md`, `context-template.md`.
