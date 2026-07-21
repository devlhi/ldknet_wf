# Context: Admin Laravel log viewer

- **Tanggal**: 2026-07-21 22:25 WIB
- **Agent**: Droid
- **Bucket**: features

## Tujuan

Menyediakan halaman web terproteksi agar admin/developer dapat melihat log Laravel produksi, menyalin isinya, memuat ulang, memilih file daily, dan membersihkan file log tanpa akses SSH.

## Keputusan

- Route `GET admin/logs` dan `POST admin/logs/clear` hanya dapat diakses level admin/developer; clear memakai CSRF, throttle, konfirmasi SweetAlert, exclusive file lock, dan truncate tanpa menghapus file aktif.
- Hanya `laravel.log` dan `laravel-YYYY-MM-DD.log` dari direktori logging terkonfigurasi yang diizinkan. Symlink/hard-link, path di luar direktori, dan identitas file yang berubah setelah dibuka ditolak.
- Pembacaan dibatasi ketat pada bagian terbaru maksimal 512 KB dan respons memakai no-store serta escaped Blade output untuk mencegah kebocoran cache/XSS.
- File terbaru dipilih berdasarkan timestamp numerik. Copy memakai Clipboard API dengan fallback browser lama.

## File yang Disentuh

- `app/Http/Controllers/Admin/LogViewerController.php`
- `routes/modules/log-viewer.php`
- `resources/views/admin/logs/index.blade.php`
- `resources/views/admin/layout.blade.php`
- `tests/Feature/AdminLogViewerTest.php`

## Validasi

- Focused: 7 PASS, 1 skip (symlink tidak diizinkan Windows), 35 assertions.
- Full suite: 68 PASS, 2 skip, 387 assertions.
- `php vendor/bin/pint --test`, `php artisan view:cache`, `php artisan route:list --path=admin/logs`, dan `git diff --check` PASS.

## Follow-up

- Deploy perubahan ke produksi, lalu buka menu Pengaturan > Laravel Logs untuk melihat kode `error_code` dan `error_title` dari webhook Meta yang gagal.
