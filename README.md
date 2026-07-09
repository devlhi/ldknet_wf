# LandakNet (ldknet_wf)

Aplikasi billing & manajemen pelanggan ISP berbasis **Laravel 12** — migrasi dari CodeIgniter 4. Terintegrasi dengan MikroTik RouterOS, GenieACS (TR-069), payment gateway (Tripay & Duitku), dan WhatsApp gateway.

## Kebutuhan Sistem

| Komponen | Versi minimal |
|----------|---------------|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Node.js & NPM | 18+ — **opsional**, hanya untuk pengembangan asset Vite. **Tidak dibutuhkan di production** |
| Ekstensi PHP | `curl`, `mbstring`, `openssl`, `pdo_mysql`, `bcmath`, `gd`, `zip` |

> Disarankan pakai **Laragon** (Windows) atau **Valet/Sail** — sudah menyediakan PHP + MySQL + Composer.

## Langkah Instalasi

### 1. Clone repository

```bash
git clone https://github.com/devlhi/ldknet_wf.git
cd ldknet_wf
```

### 2. Install dependency PHP

```bash
composer install
```

> `vendor/` sengaja tidak ikut di repo, jadi langkah ini wajib.

### 3. Install & build asset frontend (OPSIONAL — bisa dilewati di production)

Semua halaman aplikasi (admin, server, customer, guest) memakai asset statis yang **sudah jadi** di `public/assets/` — tidak perlu di-build. Vite hanya dipakai oleh halaman `welcome.blade.php` bawaan Laravel yang **tidak punya route** (tidak pernah tampil).

Jadi di **production tanpa Node.js/npm, langkah ini boleh dilewati** — aplikasi tetap jalan penuh.

Jalankan hanya jika mengembangkan asset Vite secara lokal:

```bash
npm install
npm run build
```

Untuk pengembangan (hot reload) gunakan `npm run dev`.

### 4. Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`, ganti bagian database ke MySQL:

```env
APP_NAME=LandakNet
APP_ENV=local
APP_URL=http://landaknet-laravel.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=landaknet
DB_USERNAME=root
DB_PASSWORD=
```

> Aplikasi ini berbagi database `landaknet` dengan sistem lama. Jika database sudah ada, **jangan** menjalankan migrasi yang menimpa tabel — migrasi sudah dibuat aman (skip bila tabel sudah ada).

### 5. Siapkan database

Buat database kosong bernama `landaknet` (bila belum ada), lalu:

```bash
php artisan migrate
```

Migrasi otomatis melewati tabel yang sudah ada di DB legacy (guard `hasTable`), jadi aman dijalankan pada database yang sudah terisi — `migrate` hanya **menambah** tabel baru, tidak menimpa/menghapus data lama.

### ⚠️ PERINGATAN: Migrasi di Database Production

`php artisan migrate` **aman** (hanya menambah tabel baru). Tapi perintah berikut **MENGHAPUS DATA** — jangan pernah dijalankan di production:

| Perintah | Efek |
|----------|------|
| `migrate:fresh` | DROP semua tabel lalu buat ulang → **data hilang total** |
| `migrate:refresh` | rollback semua + migrate ulang → data hilang |
| `migrate:reset` | rollback semua (semua `down()` = DROP tabel) |
| `migrate:rollback` | jalankan `down()` terakhir → DROP tabel |
| `db:wipe` | hapus seluruh isi database |

**Prosedur aman sebelum migrate di production:**

```bash
# 1. Backup database dulu (WAJIB)
mysqldump -u root -p landaknet > backup_landaknet_$(date +%Y%m%d).sql

# 2. Dry-run: lihat SQL yang AKAN jalan tanpa mengeksekusi
php artisan migrate --pretend

# 3. Baru jalankan
php artisan migrate
```

### 6. Jalankan aplikasi

```bash
php artisan serve
```

Buka `http://localhost:8000` (atau domain Laragon `http://landaknet-laravel.test`).

## Deployment di cPanel / Server Production

Panduan ini berdasarkan deployment nyata di server cPanel (AlmaLinux/CentOS). Masalah paling umum: **403 Forbidden — "Server unable to read htaccess file"** karena file di-clone sebagai `root` sehingga tidak bisa dibaca web server.

### 1. Clone & install (sebagai root atau via SSH)

```bash
cd /home/NAMAUSER
git clone https://github.com/devlhi/ldknet_wf.git public_html
cd public_html
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# edit .env: APP_URL, DB_*, APP_ENV=production, APP_DEBUG=false
php artisan migrate        # aman — hanya menambah tabel baru
```

> **npm/Node.js TIDAK dibutuhkan** — lewati `npm install` / `npm run build` (lihat catatan di atas).

### 2. Perbaiki ownership & permission (WAJIB bila clone sebagai root)

Di cPanel, semua file **harus dimiliki user akun cPanel** (bukan `root`, bukan `www-data`):

```bash
cd /home/NAMAUSER

chown -R NAMAUSER:NAMAUSER public_html
find public_html -type d -exec chmod 755 {} \;
find public_html -type f -exec chmod 644 {} \;
chmod -R 775 public_html/storage public_html/bootstrap/cache
mkdir -p public_html/public/data/absensi
chown -R NAMAUSER:NAMAUSER public_html/public/data/absensi
chmod -R 775 public_html/public/assets/logo public_html/public/data/absensi
```

> Ganti `NAMAUSER` dengan user akun cPanel (contoh: `landakne`).
> Folder `public/data/absensi` tidak ikut di git, jadi wajib dibuat manual (`mkdir -p`).

### 3. Arahkan document root ke folder `public/`

**Opsi A (disarankan)** — ubah document root domain di WHM/cPanel ke `/home/NAMAUSER/public_html/public`.

**Opsi B** — bila document root tidak bisa diubah, buat file `.htaccess` di root `public_html`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

```bash
chown NAMAUSER:NAMAUSER public_html/.htaccess
chmod 644 public_html/.htaccess
```

### 4. Bersihkan cache & verifikasi

```bash
su NAMAUSER -c "cd /home/NAMAUSER/public_html && php artisan optimize:clear"
```

Buka domain di browser — halaman login harus tampil. Bila masih error, cek `storage/logs/laravel.log`.

### Update aplikasi di production

```bash
cd /home/NAMAUSER/public_html
git pull
composer install --no-dev --optimize-autoloader   # bila composer.lock berubah
php artisan migrate                                # aman, hanya menambah
php artisan optimize:clear
chown -R NAMAUSER:NAMAUSER .                       # bila pull dijalankan sebagai root
```

## Upload Logo & Storage Symlink

**Upload logo TIDAK butuh symlink.** Logo yang diupload lewat menu **Pengaturan** disimpan langsung ke folder publik `public/assets/logo/` (lihat `SettingController.php`), yang sudah bisa diakses web. Jadi setelah clone, upload logo langsung jalan tanpa setup tambahan.

Yang perlu dipastikan hanya **izin tulis** folder logo:

```bash
# Linux / production
chmod -R 775 public/assets/logo
chown -R www-data:www-data public/assets/logo   # sesuaikan user web server
```

> **User web server berbeda per distro/panel** — `www-data` hanya ada di Debian/Ubuntu.
> Cek user yang benar dengan: `ps aux | grep -E 'nginx|httpd|php-fpm' | grep -v grep | awk '{print $1}' | sort -u`
>
> | Lingkungan | User umum |
> |---|---|
> | Debian / Ubuntu | `www-data` |
> | CentOS / RHEL / AlmaLinux | `apache` atau `nginx` |
> | cPanel / WHM | user akun cPanel (mis. `namauser:namauser`) |
> | aaPanel / BT Panel | `www` |

> Di Windows/Laragon biasanya sudah writable, tidak perlu chmod.

### Kapan `storage:link` diperlukan?

Perintah symlink Laravel:

```bash
php artisan storage:link
```

...hanya dibutuhkan **jika** ada fitur yang menyimpan file upload ke `storage/app/public` (disk `public`). Symlink ini membuat `public/storage` → `storage/app/public` supaya file bisa diakses via URL.

Saat ini upload logo **tidak** memakai disk itu, jadi `storage:link` opsional. Jalankan hanya bila nanti menambah fitur upload yang pakai `Storage::disk('public')`. Symlink ini otomatis di-ignore git (`/public/storage` sudah ada di `.gitignore`), jadi wajib dijalankan ulang di tiap server baru bila dipakai.

## Folder Upload Absensi Karyawan

Fitur **Absensi Karyawan** menyimpan foto check-in/check-out ke folder publik:

```text
public/data/absensi/
```

Folder ini harus ada dan writable oleh web server. Di Windows/Laragon biasanya cukup buat foldernya:

```powershell
mkdir public\data\absensi
```

Di Linux/production:

```bash
mkdir -p public/data/absensi
chmod -R 775 public/data/absensi
chown -R www-data:www-data public/data/absensi   # sesuaikan user web server (lihat tabel di atas)
```

Fitur absensi saat ini **tidak membutuhkan** `php artisan storage:link`, karena file selfie disimpan langsung ke `public/data/absensi`. Jika nanti upload absensi dipindah ke `storage/app/public/absensi`, barulah jalankan:

```bash
php artisan storage:link
```

Lalu pastikan URL file diarahkan lewat `public/storage/absensi`. Jangan commit file foto absensi ke git.

## Export Laporan (Excel & PDF)

### Excel

File Excel **tidak disimpan di server**. Saat tombol export ditekan, file di-generate dengan PhpSpreadsheet lalu langsung di-*stream* sebagai unduhan (`response()->streamDownload()` → `php://output`), jadi file tersimpan di folder **Downloads browser** masing-masing user, bukan di folder aplikasi.

Karena tidak menulis ke disk, tidak perlu folder khusus atau izin tulis tambahan untuk export. Nama file yang dihasilkan:

| Menu | Nama file |
| --- | --- |
| Laporan Tagihan | `laporan_tagihan.xlsx` |
| Laporan Arus Kas | `laporan_arus_kas.xlsx` |
| Data Pelanggan | `data_pelanggan.xlsx` |
| Pelanggan Baru | `data_pelanggan_baru.xlsx` |

> Nilai sel ditulis sebagai teks eksplisit (`setCellValueExplicit` TYPE_STRING) untuk mencegah formula/CSV injection.

### PDF

Tidak ada export PDF sisi server. Beberapa tombol di halaman laporan memakai ikon `fa-file-pdf`, tetapi fungsinya tetap **export Excel** (`.xlsx`) — ikon saja, bukan output PDF.

Satu-satunya keluaran PDF adalah **cetak invoice pelanggan** ([guest/invoice/detail.blade.php](resources/views/guest/invoice/detail.blade.php)) yang memakai `window.print()`. User bisa memilih **Save as PDF** dari dialog cetak browser; hasilnya juga tidak disimpan di server.

## Konfigurasi Payment Gateway

Setelah login sebagai admin, buka menu **Payment Gateway**:

### Tripay
Isi Kode Merchant, API URL, API Key, Private Key, lalu set mode Sandbox sesuai kebutuhan.
Callback URL yang didaftarkan di Tripay: `https://domain-anda/callback/tripay`

### Duitku
Isi **Kode Merchant** dan **API Key (Merchant Key)** dari dashboard Duitku, aktifkan Sandbox untuk uji coba.
Callback URL yang didaftarkan di Duitku: `https://domain-anda/callback/duitku`

Untuk memilih gateway aktif, klik **Jadikan Default** pada gateway yang diinginkan.

## Integrasi Lain

| Fitur | Setting |
|-------|---------|
| MikroTik RouterOS | Menu **Router** — tambahkan IP, user, password API |
| GenieACS (TR-069) | Menu **ACS** — isi URL server GenieACS |
| WhatsApp Gateway | Menu **WhatsApp Gateway** — dukung Wablas & Meta Official |
| SMTP / Email | Menu **Pengaturan → SMTP** (via Brevo) |

## Perintah Berguna

```bash
php artisan optimize:clear     # bersihkan semua cache
php artisan view:clear         # bersihkan cache view
php artisan config:cache       # cache config (production)
php artisan route:list         # lihat semua route
```

## Catatan Keamanan

- File `.env` berisi kredensial dan **tidak** ikut di repo — jangan pernah commit `.env`.
- Selalu gunakan mode **Sandbox** payment gateway saat pengembangan.
- Ganti `APP_DEBUG=false` di lingkungan produksi.

## Struktur Singkat

```
app/
├── Http/Controllers/    # Controller (Admin, User, Server, Callback)
├── Libraries/           # TripayPayment, DuitkuPayment, RouterosAPI, ACSRequest
├── Support/             # InvoicePayment (dispatcher), notifier, resolver
└── Models/              # Eloquent models

resources/views/
├── admin/               # Panel admin
├── server/              # Router & OLT management
├── customer/ & user/    # Halaman pelanggan
└── guest/               # Halaman publik (cek tagihan)
```

## Lisensi

Berbasis Laravel (MIT). Kode aplikasi bersifat privat/proprietary sesuai pemilik.
