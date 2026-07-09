# WA Meta Template — Rebrand ANNORTY NET + rename pelanggan_baru (PAUSED)

- 2026-07-08 ~16:35 WIB | kiro
- Status: **DI-PENDING** atas permintaan user. Kode Laravel selesai; sisa kerja di sisi Meta Dashboard.

## Yang sudah selesai (kode Laravel, terverifikasi php -l + Pint)

1. **Rebrand semua template WA Meta ke "ANNORTY NET"** (dulu "LandakNet / Landak Network").
   - Footer semua 6 template default = `Salam Hangat - ANNORTY NET`.
   - Body `notif_buka_isolir` + fallback footer sudah "ANNORTY NET".

2. **`notif_pelanggan_baru` ditolak Meta berulang** karena classifier UTILITY mendeteksi keyword kredensial (`password`, login).
   - Fix: variabel `password` DIHAPUS dari template + kedua caller (password tetap dikirim via email).
   - Body final netral: "Pendaftaran Anda Berhasil ... Informasi akun Anda telah dikirim ke email terdaftar."
   - Parameter jadi 6: `[nama, email, id_pelanggan, expdate, paket, link]` (urut = array_values caller).

3. **Nama template Meta pelanggan baru diganti** `notif_pelanggan_baru` → **`notif_daftar_berhasil`**.
   - Alasan: template Indonesia lama di Meta masih "sedang dihapus" (popup "Bahasa template pesan dihapus, coba lagi <1 menit"), bentrok nama → tidak bisa buat ulang. Ganti nama = jalan pintas.
   - CATATAN: yang diganti hanya STRING NAMA TEMPLATE META. Kolom DB `notif_pelanggan_baru` (pesan legacy shared-DB) TIDAK diubah.

## File yang disentuh (nama template Meta)

- `app/Libraries/WhatsAppMetaApi.php` — defaultTemplateDefinitions: name `notif_daftar_berhasil`, body/param final, footer ANNORTY NET.
- `app/Support/WhatsAppNotifier.php` — DEFAULT_TEMPLATE_NAMES[pelanggan_baru] = notif_daftar_berhasil.
- `app/Support/WhatsAppGatewayResolver.php` — defaultMetaSettings + encodeMetaSettings fallback = notif_daftar_berhasil.
- `app/Http/Controllers/Admin/GatewayController.php` — metaSettingsFromRequest fallback = notif_daftar_berhasil.
- `resources/views/admin/gateway/whatsapp/edit.blade.php`, `setup.blade.php`, `meta-templates.blade.php` — default value input = notif_daftar_berhasil.
- Caller param 6 (password dibuang): `CustomerController.php` ~247, `WebhookController.php` ~595.

## Sisa kerja (PENDING — bahas lain waktu)

1. **Buat template Meta baru** nama `notif_daftar_berhasil`, kategori UTILITY, body final (lihat di bawah). Jangan pakai `notif_pelanggan_baru` lagi.
2. **Template lain yang body/param-nya berubah** harus di-edit/hapus+buat ulang di Meta agar placeholder cocok: notif_tagihan(6), notif_pengingat(4), notif_tagihan_terbayar(4), notif_isolir(5), notif_buka_isolir(4).
3. **Fitur "edit template Meta dari app"** (permintaan "edit jg template meta") BELUM dibuat. Perlu `editTemplate($templateId,...)` POST ke `{graphUrl}/{template_id}` + resolve id by name + action/route/tombol UI. Saat ini app hanya CREATE (`whatsappMetaCreateTemplates`).

## Body final notif_daftar_berhasil (UTILITY)
```
Pendaftaran Anda Berhasil

Nama: {{1}}
Email: {{2}}
ID Pelanggan: {{3}}
Expdate: {{4}}
Paket: {{5}}
Link: {{6}}

Informasi akun Anda telah dikirim ke email terdaftar.

Salam Hangat

ANNORTY NET
```
