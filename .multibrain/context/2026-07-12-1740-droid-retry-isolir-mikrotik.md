# Context: Retry isolir saat Mikrotik gagal terhubung

- **Tanggal**: 2026-07-12 17:40 WIB
- **Agent**: Droid
- **Bucket**: modules-parallel

## Tujuan

Memastikan pelanggan yang sudah berstatus `Isolir` tetapi gagal diproses karena Mikrotik tidak terhubung tetap dicoba lagi pada jadwal cron berikutnya selama belum dibayar.

## Keputusan

Tidak menambah kolom atau mengubah skema DB. `AutoController::isolir()` tetap memilih semua order berstatus `Isolir`, sehingga kegagalan dibiarkan berstatus sama dan otomatis masuk retry harian. Proses diperkuat dengan pemrosesan per chunk, isolasi exception per pelanggan, disconnect di `finally`, warning log untuk router hilang/koneksi atau command gagal, serta ringkasan hasil ke CronLog melalui output task. Pelanggan yang sudah dibayar kembali `Active`, sehingga otomatis keluar dari antrean retry.

## File yang Disentuh

- `app/Http/Controllers/AutoController.php`: hardening dan observabilitas retry isolir, factory RouterOS untuk test.
- `tests/Feature/AutoControllerIsolirTest.php`: simulasi koneksi gagal, retry berikutnya sukses, dan pelanggan `Active` tidak diproses.

## Follow-up

Pastikan task `isolir` tetap enabled di menu pengaturan cron dan server menjalankan `php artisan schedule:run` setiap menit. Verifikasi live dengan router uji saat tersedia.
