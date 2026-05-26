# Daily Closing System

Sistem berbasis web untuk mencatat, mengelola, dan memantau laporan pekerjaan harian karyawan berdasarkan struktur level user dan divisi.

Repo: https://github.com/vian1/DailyApp

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8 / MariaDB
- **Frontend**: Blade templates + Bootstrap 5 (light theme via CDN, Bootstrap Icons, Inter font)
- **Auth**: Laravel Auth bawaan (session-based)
- **Dev environment**: XAMPP (PHP + MySQL), Composer

## Struktur Folder

```
DailyApp/                   ← repo root + Laravel project root
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/views/
├── routes/
├── storage/
├── tests/
├── vendor/                 ← (gitignored)
├── artisan
├── composer.json
├── pint.json
├── start.bat               ← Start MySQL + Laravel + phpMyAdmin
├── stop.bat                ← Stop semua service
├── status.bat              ← Cek status service
├── prd.md                  ← Product Requirements Document
└── README.md               ← Anda di sini
```

## Prerequisites

Pastikan terinstall:

| Tool | Versi | Catatan |
|---|---|---|
| XAMPP | 8.2+ | Untuk PHP + MySQL. Default path `C:\xampp\` |
| Composer | 2.x | Tidak harus global — script setup akan download `composer.phar` lokal |
| Git | — | Untuk clone repo |

Cek versi PHP: `C:\xampp\php\php.exe -v`

## Setup Pertama Kali

```powershell
# 1. Clone repo
git clone git@github.com:vian1/DailyApp.git
cd DailyApp

# 2. Install dependencies Laravel
C:\xampp\php\php.exe composer install
# Catatan: kalau composer belum ada, download dulu:
#   C:\xampp\php\php.exe -r "copy('https://getcomposer.org/installer','c.php');"
#   C:\xampp\php\php.exe c.php --install-dir=. --filename=composer
#   del c.php

# 3. Setup environment
copy .env.example .env
C:\xampp\php\php.exe artisan key:generate

# 4. Konfigurasi .env (edit manual)
#    DB_DATABASE=daily_closing
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Pastikan MySQL berjalan, lalu buat database
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE daily_closing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 6. Jalankan migrasi + seeder
C:\xampp\php\php.exe artisan migrate:fresh --seed

# 7. Jalankan app
.\start.bat
```

## Menjalankan Aplikasi

Setelah setup selesai, gunakan script di root:

| Script | Fungsi |
|---|---|
| `start.bat` | Cek/start MySQL → start Laravel di port 8000 → start phpMyAdmin di port 8080 → buka browser |
| `status.bat` | Tampilkan status MySQL, Laravel server, phpMyAdmin + URL aktif |
| `stop.bat` | Bunuh semua proses Laravel + phpMyAdmin (worker termasuk); opsional stop MySQL |

URL setelah start:

- **App**: http://127.0.0.1:8000
- **phpMyAdmin**: http://127.0.0.1:8080 (user: `root`, password kosong)

## Akun Default (dari Seeder)

Password semua: `password`

| Email | Level | Role | Divisi | Membawahi |
|---|---|---|---|---|
| admin@daily.test | 1 | Owner + Super Admin | Admin Project | — |
| direktur@daily.test | 1 | Owner | Direktur | — |
| manager@daily.test | 2 | Manager | Engineer | Engineer, Hardware, Software |
| manager2@daily.test | 2 | Manager | Marketing | Marketing, Publikasi |
| leader@daily.test | 3 | Leader | Engineer | — |
| leader2@daily.test | 3 | Leader | Marketing | — |
| staff@daily.test | 4 | Staff | Engineer | — |
| rina@daily.test | 4 | Staff | Software | — |
| helper@daily.test | 4 | Staff | Helper | — |

## Hierarki Akses

| Role | Lihat Laporan | Kelola User |
|---|---|---|
| Super Admin (flag) | Semua laporan (tidak terbatas divisi) | Ya (CRUD user) |
| Owner (L1) | Semua laporan level 2/3/4 (semua divisi) | Tidak |
| Manager (L2) | Laporan level 3/4 dari `managed_divisions` saja | Tidak |
| Leader (L3) | Laporan level 4 dari divisi sendiri saja | Tidak |
| Staff (L4) | Hanya laporan sendiri | Tidak |

Catatan: **Super Admin** adalah flag terpisah dari level — bisa di-set pada user level berapapun.

## Modul Utama

- **Authentication** — login, logout, proteksi route via middleware
- **Manajemen User** (Super Admin only) — CRUD user, set level/divisi/jabatan, toggle Super Admin & is_active
- **Laporan Harian**
  - Buat Laporan (1 hari = 1 laporan per user, validasi unique)
  - Laporan Saya — laporan milik user yang login
  - Laporan Tim / Semua Laporan — laporan bawahan (filter otomatis berdasarkan level + divisi)
  - Filter: tanggal, nama, divisi, status lembur, butuh bantuan
  - Edit & hapus (hanya milik sendiri)
- **Dashboard** — stat cards (laporan hari ini, minggu ini, lembur, butuh bantuan) + laporan terbaru, semua difilter sesuai akses

## Daftar Divisi & Jabatan

**Divisi** (dropdown tetap):
Direktur, Komisaris, Marketing, RnD, Software, Admin Project, Engineer, Tax Officer, Accounting, Purchasing, HSE, Helper, HRD, Publikasi, Hardware

**Jabatan** (dropdown tetap):
Direktur, Komisaris, Manager, Leader, Staff

Untuk menambah/ubah opsi, edit konstanta `User::DIVISIONS` atau `User::POSITIONS` di [app/Models/User.php](app/Models/User.php).

## Perintah Artisan yang Sering Dipakai

```powershell
# Dari root repo
C:\xampp\php\php.exe artisan migrate:fresh --seed     # reset DB + seed ulang
C:\xampp\php\php.exe artisan migrate                   # apply migration baru saja
C:\xampp\php\php.exe artisan db:seed                   # re-run seeder tanpa reset
C:\xampp\php\php.exe artisan view:clear                # bersihkan view cache
C:\xampp\php\php.exe artisan route:list                # lihat semua route
C:\xampp\php\php.exe artisan tinker                    # REPL untuk eksplor model
```

## Workflow Git

```powershell
git pull                       # tarik perubahan terbaru
git status                     # cek file yang berubah
git add .
git commit -m "deskripsi"
git push
```

File yang **tidak akan masuk Git** (sudah di-ignore):

- `.env` — kredensial DB lokal
- `vendor/` — dependencies Composer
- `storage/logs/*.log` — log Laravel
- `.server.pid`, `.pma.pid` — PID file runtime
- `composer` / `composer.phar` — binary composer lokal

## Troubleshooting

**Apache phpMyAdmin tidak bisa diakses?**
- XAMPP Apache butuh port 443 yang sering bentrok dengan VPN. Kita pakai workaround: phpMyAdmin di-serve via PHP built-in server di port 8080. Lihat `start.bat` baris `phpMyAdmin`.

**MySQL tidak mau start?**
- Pastikan tidak ada proses MySQL lain yang sedang berjalan (`Get-Process mysqld`).
- Cek port 3306: `Get-NetTCPConnection -LocalPort 3306`.

**Laravel error "Class 'X' not found"?**
- Jalankan `composer dump-autoload` di root repo.

**Form validasi rejected tapi pesan tidak jelas?**
- Cek `storage/logs/laravel.log`.

## Lisensi & Penulis

Internal project. Maintainer: vian1 <vant.cemut@gmail.com>
