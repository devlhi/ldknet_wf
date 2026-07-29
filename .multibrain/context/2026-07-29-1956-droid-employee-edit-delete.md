# Context: Edit dan hapus data karyawan

- **Tanggal**: 2026-07-29 19:56 WIB
- **Agent**: Droid
- **Bucket**: features

## Tujuan

Memungkinkan admin/developer mengedit nama, email, dan password karyawan serta menghapus akun karyawan dari halaman Data Karyawan tanpa mengubah skema DB.

## Keputusan

Form edit memakai modal per karyawan. Nama dan email wajib divalidasi sesuai batas kolom legacy, email harus unik kecuali milik akun yang sedang diedit, dan password opsional agar hash lama tetap dipertahankan saat kolom kosong. Penghapusan hanya menerima akun level `technician`, memakai POST+CSRF dan konfirmasi SweetAlert, serta menghapus akun, riwayat absensi, dan foto check-in/check-out terkait. Nama file foto harus basename-only sebelum file dihapus untuk mencegah path traversal.

## File yang Disentuh

- `app/Http/Controllers/Admin/AttendanceController.php`: update data karyawan dan penghapusan akun, absensi, serta foto.
- `routes/modules/attendance.php`: endpoint POST update dan delete karyawan.
- `resources/views/admin/absensi/karyawan.blade.php`: modal edit dan tombol hapus dengan konfirmasi.
- `tests/Feature/AdminEmployeeManagementTest.php`: regresi edit, password opsional, email duplikat, hapus data/foto, dan pembatasan akun non-technician.

## Validasi

Focused 4 PASS/29 assertions. Full suite 72 PASS/2 skip/405 assertions. Pint, Blade cache, route listing, `git diff --check`, dan review spesialis lulus setelah temuan orphan foto diperbaiki.

## Follow-up

Perubahan belum di-commit atau di-push.
