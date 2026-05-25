# PRD.md — Sistem Pelaporan Pekerjaan Harian

## 1. Nama Produk

**Daily Closing System**

Sistem berbasis web untuk mencatat, mengelola, dan memantau laporan pekerjaan harian karyawan berdasarkan struktur level user.

---

## 2. Latar Belakang

Perusahaan membutuhkan sistem pelaporan pekerjaan harian agar setiap karyawan dapat mencatat pekerjaan yang sudah selesai, pekerjaan yang belum selesai, hambatan, kebutuhan bantuan leader, rencana kerja besok, status lembur, serta catatan tambahan.

Saat ini laporan harian masih berpotensi tersebar melalui chat, dokumen manual, atau format tidak seragam. Hal ini menyulitkan leader untuk memantau progres pekerjaan, mengetahui hambatan tim, dan mengambil keputusan secara cepat.

Dengan adanya **Daily Closing System**, laporan pekerjaan menjadi lebih rapi, terstruktur, mudah dicari, dan dapat dipantau sesuai level akses masing-masing user.

---

## 3. Tujuan Produk

Tujuan utama sistem ini adalah:

1. Membuat proses laporan harian menjadi standar dan terdokumentasi.
2. Memudahkan karyawan mengisi laporan pekerjaan setiap hari.
3. Memudahkan leader melihat laporan bawahan sesuai level akses.
4. Membantu manajemen memantau pekerjaan selesai, belum selesai, hambatan, lembur, dan kebutuhan bantuan.
5. Menjadi dasar pengembangan fitur lanjutan seperti approval, KPI, rekap bulanan, export Excel/PDF, dan notifikasi.

---

## 4. Platform & Teknologi

### 4.1 Platform

- Web Application
- Responsive untuk desktop dan mobile browser

### 4.2 Teknologi

- Backend: Laravel
- Database: MySQL
- Frontend: Blade / Laravel UI / Laravel Breeze / Laravel + Vue dapat dipilih bertahap
- Authentication: Laravel Auth / Breeze
- Authorization: Role-based access control
- Deployment: VPS / Shared Hosting yang mendukung Laravel dan MySQL

---

## 5. Target Pengguna

Sistem digunakan oleh seluruh karyawan dan leader perusahaan yang wajib membuat atau memantau laporan pekerjaan harian.

### 5.1 Level User

| Level | Nama Peran Sementara | Hak Akses |
|---|---|---|
| Level 1 | Super Admin / Manajemen Pusat | Bisa melihat semua laporan dari level 2, 3, dan 4 |
| Level 2 | Manager / Kepala Divisi | Bisa melihat laporan level 3 dan 4 |
| Level 3 | Leader / Supervisor | Bisa melihat laporan level 4 |
| Level 4 | Staff / Karyawan | Hanya bisa melihat laporannya sendiri |

Catatan: Nama role dapat disesuaikan kembali dengan struktur organisasi perusahaan.

---

## 6. Scope Produk Versi Awal

### 6.1 Fitur Masuk Scope

Untuk versi awal, fitur yang dibuat adalah:

1. Login user.
2. Manajemen user sederhana.
3. Pengaturan level user.
4. Input laporan pekerjaan harian.
5. Edit laporan milik sendiri.
6. Lihat detail laporan.
7. List laporan berdasarkan hak akses level.
8. Filter laporan berdasarkan tanggal, nama, divisi, status lembur, dan status butuh bantuan leader.
9. Dashboard ringkas.
10. Validasi agar user tidak membuat laporan ganda pada tanggal yang sama.
11. Database MySQL untuk penyimpanan laporan.

### 6.2 Fitur Belum Masuk Scope Versi Awal

Fitur berikut belum dibuat di versi awal, tetapi dapat dikembangkan pada tahap berikutnya:

1. Approval laporan oleh leader.
2. Komentar leader pada laporan.
3. Export Excel/PDF.
4. Notifikasi WhatsApp/email.
5. Rekap mingguan dan bulanan.
6. KPI otomatis.
7. Attachment foto pekerjaan.
8. Mobile app Android/iOS.
9. Integrasi absensi.
10. Integrasi payroll/lembur.

---

## 7. User Flow Utama

### 7.1 Flow Level 4 / Staff

1. User login.
2. User masuk ke dashboard.
3. User klik menu **Buat Laporan Harian**.
4. User mengisi form laporan.
5. User submit laporan.
6. Sistem menyimpan laporan.
7. User dapat melihat riwayat laporannya sendiri.

### 7.2 Flow Level 3 / Leader

1. User login.
2. User melihat dashboard.
3. User dapat membuat laporan sendiri.
4. User dapat melihat laporan level 4.
5. User dapat memfilter laporan berdasarkan tanggal, nama, divisi, atau status bantuan leader.

### 7.3 Flow Level 2 / Manager

1. User login.
2. User melihat dashboard.
3. User dapat membuat laporan sendiri.
4. User dapat melihat laporan level 3 dan level 4.
5. User dapat memantau hambatan dan kebutuhan bantuan dari tim.

### 7.4 Flow Level 1 / Super Admin

1. User login.
2. User dapat melihat seluruh laporan dari level 2, 3, dan 4.
3. User dapat melihat rekap seluruh divisi.
4. User dapat mengelola data user dan level user.

---

## 8. Format Laporan Harian

Form laporan harian mengikuti format berikut:

```text
📋 DAILY CLOSING SYSTEM

📅 Tanggal
👤 Nama
🏢 Divisi/Jabatan

🕒 Status Lembur
⏱️ Jam Lembur

1️⃣ Pekerjaan yang sudah selesai
2️⃣ Pekerjaan yang belum selesai
3️⃣ Hambatan yang ada
4️⃣ Butuh bantuan leader
   Jika ya, jelaskan
5️⃣ Planning besok

⏰ Jam selesai kerja
📝 Catatan tambahan
```

---

## 9. Field Form Laporan

| Field | Tipe Data | Wajib | Keterangan |
|---|---|---|---|
| Tanggal | Date | Ya | Tanggal laporan |
| Nama | Auto dari user | Ya | Diambil dari data user |
| Divisi | Auto / Dropdown | Ya | Divisi user |
| Jabatan | Auto / Input | Ya | Jabatan user |
| Status Lembur | Boolean / Enum | Ya | Ya / Tidak |
| Jam Mulai Lembur | Time | Jika lembur ya | Contoh: 17:00 |
| Jam Selesai Lembur | Time | Jika lembur ya | Contoh: 20:00 |
| Pekerjaan Selesai | Textarea | Ya | Bisa diisi multi item |
| Pekerjaan Belum Selesai | Textarea | Tidak | Bisa kosong jika tidak ada |
| Hambatan | Textarea | Tidak | Bisa kosong jika tidak ada |
| Butuh Bantuan Leader | Boolean / Enum | Ya | Ya / Tidak |
| Penjelasan Bantuan | Textarea | Jika butuh bantuan ya | Menjelaskan bantuan yang dibutuhkan |
| Planning Besok | Textarea | Ya | Rencana pekerjaan berikutnya |
| Jam Selesai Kerja | Time | Ya | Contoh: 20:05 |
| Catatan Tambahan | Textarea | Tidak | Informasi tambahan |

---

## 10. Hak Akses Sistem

### 10.1 Aturan Akses Laporan

| User Login | Bisa Melihat Laporan |
|---|---|
| Level 1 | Level 2, Level 3, Level 4 |
| Level 2 | Level 3, Level 4 |
| Level 3 | Level 4 |
| Level 4 | Laporan milik sendiri |

### 10.2 Catatan Penting

Untuk versi awal, level akses berdasarkan angka level user.

Aturan sederhana:

```text
Level 1 melihat user dengan level > 1
Level 2 melihat user dengan level > 2
Level 3 melihat user dengan level > 3
Level 4 hanya melihat user_id miliknya sendiri
```

Dengan aturan ini, sistem lebih mudah dibuat terlebih dahulu.

---

## 11. Modul Sistem

## 11.1 Modul Authentication

### Deskripsi

Modul untuk login dan logout user.

### Fitur

- Login
- Logout
- Proteksi halaman dengan middleware auth

### Acceptance Criteria

- User tidak bisa masuk dashboard tanpa login.
- User yang login diarahkan ke dashboard.
- User bisa logout dari sistem.

---

## 11.2 Modul Manajemen User

### Deskripsi

Modul untuk mengelola data user.

### Fitur

- Tambah user
- Edit user
- Nonaktifkan user
- Set level user
- Set divisi dan jabatan

### Field User

| Field | Tipe |
|---|---|
| name | string |
| email | string unique |
| password | string |
| level | tinyInteger |
| division | string nullable |
| position | string nullable |
| is_active | boolean |

### Acceptance Criteria

- Level 1 dapat mengelola user.
- Email user tidak boleh duplikat.
- Password tersimpan dalam bentuk hash.
- User nonaktif tidak bisa login.

---

## 11.3 Modul Input Laporan Harian

### Deskripsi

Modul untuk membuat laporan pekerjaan harian.

### Fitur

- Buat laporan harian
- Edit laporan harian milik sendiri
- Validasi laporan per tanggal
- Simpan pekerjaan selesai, belum selesai, hambatan, bantuan leader, planning besok, lembur, dan catatan tambahan

### Acceptance Criteria

- User hanya bisa membuat laporan untuk dirinya sendiri.
- User tidak bisa membuat lebih dari satu laporan pada tanggal yang sama.
- Jika status lembur adalah "Ya", jam lembur wajib diisi.
- Jika butuh bantuan leader adalah "Ya", penjelasan bantuan wajib diisi.
- Setelah submit, laporan tampil di daftar laporan user.

---

## 11.4 Modul List Laporan

### Deskripsi

Modul untuk menampilkan daftar laporan sesuai hak akses level user.

### Fitur

- List laporan
- Filter tanggal
- Filter nama
- Filter divisi
- Filter status lembur
- Filter butuh bantuan leader
- View detail laporan

### Acceptance Criteria

- Level 1 bisa melihat laporan level 2, 3, dan 4.
- Level 2 bisa melihat laporan level 3 dan 4.
- Level 3 bisa melihat laporan level 4.
- Level 4 hanya bisa melihat laporan miliknya sendiri.
- User tidak bisa membuka detail laporan di luar hak aksesnya.

---

## 11.5 Modul Detail Laporan

### Deskripsi

Halaman untuk melihat isi lengkap laporan harian.

### Data yang Ditampilkan

- Tanggal
- Nama
- Divisi/Jabatan
- Status lembur
- Jam lembur
- Pekerjaan selesai
- Pekerjaan belum selesai
- Hambatan
- Butuh bantuan leader
- Penjelasan bantuan
- Planning besok
- Jam selesai kerja
- Catatan tambahan

### Acceptance Criteria

- Detail laporan tampil sesuai format daily closing.
- User hanya bisa membuka laporan sesuai hak aksesnya.

---

## 11.6 Modul Dashboard

### Deskripsi

Dashboard menampilkan ringkasan laporan berdasarkan hak akses user.

### Informasi Dashboard Versi Awal

- Jumlah laporan hari ini
- Jumlah laporan minggu ini
- Jumlah user yang sudah submit laporan hari ini
- Jumlah laporan dengan status lembur
- Jumlah laporan yang butuh bantuan leader
- Daftar laporan terbaru

### Acceptance Criteria

- Data dashboard mengikuti batasan level akses user.
- Level 4 hanya melihat statistik laporan miliknya sendiri.
- Level 1, 2, dan 3 melihat statistik dari user yang berada di bawah levelnya.

---

## 12. Struktur Database Awal

## 12.1 Tabel users

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    level TINYINT NOT NULL COMMENT '1=Super Admin, 2=Manager, 3=Leader, 4=Staff',
    division VARCHAR(255) NULL,
    position VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 12.2 Tabel daily_reports

```sql
CREATE TABLE daily_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    report_date DATE NOT NULL,
    overtime_status BOOLEAN DEFAULT FALSE,
    overtime_start TIME NULL,
    overtime_end TIME NULL,
    completed_work TEXT NOT NULL,
    unfinished_work TEXT NULL,
    obstacles TEXT NULL,
    need_leader_help BOOLEAN DEFAULT FALSE,
    leader_help_description TEXT NULL,
    tomorrow_plan TEXT NOT NULL,
    work_finished_at TIME NOT NULL,
    additional_notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT fk_daily_reports_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT unique_user_report_date
        UNIQUE (user_id, report_date)
);
```

---

## 13. Rekomendasi Struktur Laravel

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── DailyReportController.php
│   │   └── UserController.php
│   ├── Middleware/
│   │   └── CheckUserActive.php
│   └── Requests/
│       └── DailyReportRequest.php
├── Models/
│   ├── User.php
│   └── DailyReport.php
routes/
├── web.php
resources/
├── views/
│   ├── dashboard.blade.php
│   ├── daily-reports/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── edit.blade.php
│   │   └── show.blade.php
│   └── users/
│       ├── index.blade.php
│       ├── create.blade.php
│       └── edit.blade.php
database/
├── migrations/
│   ├── create_users_table.php
│   └── create_daily_reports_table.php
```

---

## 14. Rekomendasi Route Laravel

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::resource('/daily-reports', DailyReportController::class);

    Route::middleware(['can:manage-users'])->group(function () {
        Route::resource('/users', UserController::class);
    });
});
```

---

## 15. Logic Hak Akses Query Laporan

Contoh logic sederhana untuk query laporan:

```php
$user = auth()->user();

$query = DailyReport::with('user');

if ($user->level == 1) {
    $query->whereHas('user', function ($q) {
        $q->whereIn('level', [2, 3, 4]);
    });
} elseif ($user->level == 2) {
    $query->whereHas('user', function ($q) {
        $q->whereIn('level', [3, 4]);
    });
} elseif ($user->level == 3) {
    $query->whereHas('user', function ($q) {
        $q->where('level', 4);
    });
} else {
    $query->where('user_id', $user->id);
}

$reports = $query->latest()->paginate(20);
```

---

## 16. Validasi Form Laporan

Validasi awal:

```php
[
    'report_date' => ['required', 'date'],
    'overtime_status' => ['required', 'boolean'],
    'overtime_start' => ['nullable', 'required_if:overtime_status,1', 'date_format:H:i'],
    'overtime_end' => ['nullable', 'required_if:overtime_status,1', 'date_format:H:i'],
    'completed_work' => ['required', 'string'],
    'unfinished_work' => ['nullable', 'string'],
    'obstacles' => ['nullable', 'string'],
    'need_leader_help' => ['required', 'boolean'],
    'leader_help_description' => ['nullable', 'required_if:need_leader_help,1', 'string'],
    'tomorrow_plan' => ['required', 'string'],
    'work_finished_at' => ['required', 'date_format:H:i'],
    'additional_notes' => ['nullable', 'string'],
]
```

---

## 17. Tampilan Halaman

## 17.1 Halaman Login

Komponen:

- Email
- Password
- Tombol Login

---

## 17.2 Dashboard

Komponen:

- Card jumlah laporan hari ini
- Card jumlah laporan minggu ini
- Card laporan lembur
- Card laporan butuh bantuan leader
- Tabel laporan terbaru

---

## 17.3 Halaman List Laporan

Komponen:

- Filter tanggal
- Filter nama
- Filter divisi
- Filter status lembur
- Filter butuh bantuan leader
- Tabel laporan

Kolom tabel:

| Kolom |
|---|
| Tanggal |
| Nama |
| Divisi/Jabatan |
| Status Lembur |
| Butuh Bantuan |
| Jam Selesai Kerja |
| Aksi |

---

## 17.4 Halaman Form Laporan

Komponen:

- Tanggal
- Status lembur
- Jam lembur
- Pekerjaan selesai
- Pekerjaan belum selesai
- Hambatan
- Butuh bantuan leader
- Penjelasan bantuan
- Planning besok
- Jam selesai kerja
- Catatan tambahan
- Tombol Simpan

---

## 17.5 Halaman Detail Laporan

Tampilan detail dibuat menyerupai format laporan:

```text
📋 DAILY CLOSING SYSTEM

📅 Tanggal : 25 Mei 2026
👤 Nama : Budi Santoso
🏢 Divisi/Jabatan : Produksi – Teknisi Assembly

🕒 Status Lembur : Ya
⏱️ Jam Lembur : 17.00 – 20.00 WIB

1️⃣ Pekerjaan yang sudah selesai

- Assembly panel telemetry 3 unit selesai
- Wiring box kontrol selesai
- QC relay dan power supply selesai

2️⃣ Pekerjaan yang belum selesai

- Labeling kabel panel unit ke-4

3️⃣ Hambatan yang ada

- Stok terminal block menipis

4️⃣ Butuh bantuan leader
Ya

Jika ya, jelaskan:

- Approval pembelian material

5️⃣ Planning besok

- Menyelesaikan panel unit ke-4
- QC final dan packing

⏰ Jam selesai kerja : 20.05 WIB

📝 Catatan tambahan :

- Lembur untuk mengejar target pengiriman
```

---

## 18. Non-Functional Requirements

### 18.1 Security

- Semua halaman wajib login.
- Password wajib di-hash.
- User tidak boleh mengakses laporan di luar hak aksesnya.
- Validasi dilakukan di backend.
- Gunakan CSRF protection bawaan Laravel.
- Gunakan policy/gate untuk otorisasi akses laporan.

### 18.2 Performance

- List laporan menggunakan pagination.
- Index database disarankan pada:
  - user_id
  - report_date
  - overtime_status
  - need_leader_help

### 18.3 Usability

- Form harus mudah diisi dari HP.
- Textarea harus cukup besar.
- Tombol submit mudah terlihat.
- Filter laporan sederhana dan cepat dipahami.

### 18.4 Maintainability

- Controller dipisah berdasarkan modul.
- Validasi menggunakan Form Request.
- Hak akses menggunakan Policy/Gate.
- Query laporan dibuat reusable agar mudah dikembangkan.

---

## 19. Index Database yang Disarankan

```sql
CREATE INDEX idx_daily_reports_user_id ON daily_reports(user_id);
CREATE INDEX idx_daily_reports_report_date ON daily_reports(report_date);
CREATE INDEX idx_daily_reports_overtime_status ON daily_reports(overtime_status);
CREATE INDEX idx_daily_reports_need_leader_help ON daily_reports(need_leader_help);
```

---

## 20. Milestone Pengembangan

### Phase 1 — MVP

Target:

- Login
- Manajemen user
- Level user
- Input laporan harian
- List laporan sesuai level akses
- Detail laporan
- Dashboard sederhana

### Phase 2 — Monitoring & Rekap

Target:

- Filter lanjutan
- Rekap mingguan
- Rekap bulanan
- Export Excel
- Export PDF

### Phase 3 — Approval & Bantuan Leader

Target:

- Approval laporan
- Komentar leader
- Status tindak lanjut hambatan
- Status bantuan leader

### Phase 4 — Notifikasi & Integrasi

Target:

- Reminder isi laporan
- Notifikasi laporan butuh bantuan leader
- Integrasi WhatsApp/email
- Integrasi absensi

### Phase 5 — KPI & Analitik

Target:

- Rekap produktivitas
- Grafik laporan per divisi
- Grafik lembur
- Grafik hambatan
- KPI karyawan/tim

---

## 21. Acceptance Criteria MVP

MVP dianggap selesai jika:

1. User bisa login.
2. Level user bisa disimpan.
3. User bisa membuat laporan harian.
4. User tidak bisa membuat laporan ganda pada tanggal yang sama.
5. Level 1 bisa melihat laporan level 2, 3, dan 4.
6. Level 2 bisa melihat laporan level 3 dan 4.
7. Level 3 bisa melihat laporan level 4.
8. Level 4 hanya bisa melihat laporan sendiri.
9. User tidak bisa membuka detail laporan di luar hak akses.
10. Dashboard menampilkan ringkasan laporan sesuai hak akses.
11. Data tersimpan di MySQL.
12. Form laporan sesuai format Daily Closing System.

---

## 22. Risiko dan Catatan

| Risiko | Dampak | Solusi |
|---|---|---|
| User lupa mengisi laporan | Data tidak lengkap | Tambahkan reminder di phase berikutnya |
| Struktur level berubah | Hak akses perlu disesuaikan | Gunakan role/permission jika sistem semakin kompleks |
| Banyak laporan dalam jangka panjang | Query lambat | Gunakan index dan pagination |
| Format laporan berubah | Perlu update form | Buat field fleksibel dan modular |
| Leader perlu approval | Belum ada di MVP | Masuk phase 3 |

---

## 23. Catatan Pengembangan Lanjutan

Untuk tahap berikutnya, sistem dapat dikembangkan menjadi:

1. Sistem approval laporan.
2. Daily closing dengan tanda tangan digital.
3. Rekap otomatis untuk meeting mingguan.
4. Analisis hambatan terbanyak.
5. Notifikasi otomatis ke leader jika ada hambatan.
6. KPI berdasarkan laporan harian.
7. Integrasi dengan sistem absensi.
8. Upload foto bukti pekerjaan.
9. Mobile app untuk input laporan lapangan.
10. AI summary untuk membuat ringkasan laporan harian/mingguan otomatis.

---

## 24. Kesimpulan

Daily Closing System versi awal difokuskan untuk membuat laporan pekerjaan harian yang rapi, mudah digunakan, dan memiliki aturan akses berdasarkan level user.

Dengan menggunakan Laravel dan MySQL, sistem ini dapat dibuat secara bertahap dari MVP sederhana lalu dikembangkan menjadi sistem monitoring pekerjaan, approval, KPI, dan analitik produktivitas tim.
