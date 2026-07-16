# Context: Penutupan Celah Lazy-Loading dan Audit Kritis

- **Timestamp**: 2026-07-16 14:48 WIB
- **Agent**: Droid
- **Topik**: Penutupan celah *lazy-load* seluruh dashboard dan perbaikan keamanan kritis
- **File diubah**:
  - `app/Http/Controllers/Admin/AdminController.php`
  - `app/Http/Controllers/Admin/FinanceController.php`
  - `app/Http/Controllers/Admin/GangguanController.php`
  - `app/Http/Controllers/Admin/CustomerController.php`
  - `app/Http/Controllers/Admin/NmsController.php`
  - `app/Http/Controllers/Server/RouterController.php`
  - `app/Http/Controllers/User/UserController.php`
  - Blade views terkait dan file test PHPUnit

## Detail Pekerjaan

### 1. Perbaikan Kritis Keamanan (Pass 2)
Melalui audit paralel, sejumlah bug logika bisnis berhasil diidentifikasi dan ditutup:
- **Finance Race Condition**: Menambahkan `lockForUpdate()` di `AccSalesInvoiceController` dan `AccPurchaseBillController` untuk mencegah pembayaran ganda kas secara simultan.
- **Validasi Finansial**: Validasi subtotal akun keuangan yang kini diwajibkan sebagai kas sesungguhnya (`is_cash = 1`), serta validasi kuantitas diskon.
- **NMS Exposure**: Endpoints ping/metrics publik RouterOS/NMS (`map-data`, `status`) sebelumnya rentan terhadap pindaian port oleh peretas tak terotentikasi. Guard *signed URL middleware* dan refaktor kueri agar mengambil riwayat cache database bukan probe jaringan.
- **Privilege Escalation**: `ManageController` tidak lagi mengizinkan Admin menghapus/reset sandi pengguna Developer.
- **Bypass Sesi Pengguna non-aktif**: Menambahkan pemeriksaan blok statis `isActive()` pada `CheckLevel.php` dan `AuthController.php`.

### 2. Penutupan Celah *Lazy-Loading* Penuh
Sebelumnya, *Admin Dashboard*, *User Dashboard*, serta sejumlah *view* *Customer* masih mengeksekusi kueri otomatis (termasuk *poll* data ke Router Mikrotik) sewaktu halaman dimuat. 
- Guard boolean `show_data=1` diaktifkan ke:
  - `AdminController::index()`, `AdminController::transactions()`, `AdminController::mikrotikStats()`
  - `UserController::index()`
  - `GangguanController::cetak` (Pencegahan pencetakan *fetch* 1.000 riwayat tanpa validasi muat).
  - `CustomerController::customer_detail` (Pencegahan API mikrotik dimuat sebelum tombol diklik).
  - JS polling internal untuk grafik Mikrotik/NMS telah dikurung di dalam `@if($showData)` Blade state dan paramater POST `show_data: 1` diinjeksikan pada URL AJAX untuk mencegah interval membocorkan eksekusi pre-aktivasi.
- **Default Tahun 2026 Filter**: Filter dropdown *Tahun* yang ada pada semua modul *Finance* / laporan pendapatan telah disematkan nilai default DB-agnostik (`YEAR()` atau `strftime`) agar memaksa opsi "2026".

### 3. Simplify Review
Pekerjaan divalidasi dengan agen spesialis untuk mengurangi reduplikasi. Tidak ada interval *zombie* dan *leaks* tersisa pada Javascript grafik dan pengingat. 

Semua perubahan *test coverage* dinyatakan lulus (51 tests / 432 assertions) tanpa merusak skema legasi CI4, dan dikomit sebagai:
- `f3a070c Terapkan lazy loading pada dashboard utama dan buat default tahun filter 2026`
- `135618a Terapkan show_data pada sisa endpoint dan perbaiki kebocoran poll JS`
- `e816191 Perkuat keamanan dan integritas data`

Pekerjaan dinyatakan **Selesai**.
