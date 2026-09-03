# API Internal — Rekap Sanksi Laporan Harian untuk Payroll

Endpoint service-to-service supaya payroll di HRIS (`backend_absensi`) bisa menarik
jumlah hari kena sanksi laporan harian per pegawai dalam satu periode gaji.

Arahnya **pull dari HRIS ke Daily**: Daily pemilik data laporan harian, HRIS yang
bertanya saat menghitung gaji. Daily tidak pernah mendorong angka ini ke HRIS.

## Perubahan 3 September 2026 — hari bolong ikut dihitung

Sebelumnya `late_days` hanya menghitung laporan yang **terkirim tapi telat** (lewat
pukul 21:00). Pegawai yang **tidak mengisi laporan sama sekali** tidak pernah muncul di
angka itu, karena tanpa baris laporan tidak ada penanda telat yang bisa dibaca. Efeknya
terbalik: telat satu jam kena sanksi, bolong dua hari penuh lolos.

Mulai sekarang `late_days` / `late_dates` **menggabungkan keduanya** — hari telat dan
hari bolong. Rincian hari bolong tetap bisa dilihat terpisah di field baru
`missing_days` / `missing_dates`.

### Yang perlu dilakukan sisi HRIS

**Tidak ada perubahan kode yang wajib.** Nama dan bentuk field lama dipertahankan
persis supaya perhitungan payroll yang sudah jalan langsung ikut benar tanpa disentuh.

Yang perlu diperhatikan hanya dua hal:

1. **Angka akan naik.** Untuk periode yang sama, `late_days` bisa lebih besar dari
   sebelumnya. Ini disengaja, bukan bug. Kalau ada rekap gaji lama yang dihitung ulang,
   hasilnya tidak akan sama dengan cetakan lama.
2. **Label di UI/slip sebaiknya diperbaiki.** Kalau di HRIS tertulis "hari terlambat
   lapor", tulisannya sudah tidak akurat lagi — isinya sekarang telat **dan** bolong.
   Usulan: "hari kena sanksi laporan". `missing_dates` bisa dipakai untuk memecah
   rinciannya kalau perlu ditampilkan ke pegawai.

## Endpoint

```
GET {DAILY_APP_URL}/api/internal/payroll/daily-report-late
```

### Header wajib

```
X-Internal-Secret: <nilai DAILY_INTERNAL_SECRET>
Accept: application/json
```

Sama persis dengan endpoint sinkron cuti: nilainya harus sama dengan
`ABSENSI_BRIDGE_SECRET` di `.env` Daily. Header salah/kosong dijawab `403`. Kalau
secret di sisi Daily kosong, semua request ditolak — gagal tertutup. Jangan tulis
nilainya di repo, tiket, atau chat grup.

### Query parameter

| Field | Wajib | Keterangan |
|---|---|---|
| `start` | ya | `Y-m-d`. Awal periode gaji. |
| `end` | ya | `Y-m-d`, tidak boleh sebelum `start`. Akhir periode gaji. |
| `emails[]` | ya | Daftar email pegawai, minimal satu. Tidak membedakan huruf besar/kecil. |

## Contoh response

```json
{
  "success": true,
  "data": [
    {
      "email": "budi@bejogja.com",
      "late_days": 4,
      "late_dates": ["2026-09-01", "2026-09-07", "2026-09-08", "2026-09-09"],
      "missing_days": 3,
      "missing_dates": ["2026-09-07", "2026-09-08", "2026-09-09"]
    }
  ]
}
```

| Field | Arti |
|---|---|
| `late_days` | **Total hari kena sanksi** — gabungan telat dan bolong. Ini angka yang dipakai payroll. |
| `late_dates` | Tanggalnya, urut naik, tanpa duplikat. |
| `missing_days` | Bagian yang berasal dari hari bolong saja. Rincian, bukan angka tambahan. |
| `missing_dates` | Tanggalnya. Selalu himpunan bagian dari `late_dates`. |

`missing_days` **sudah termasuk** di dalam `late_days`. Jangan dijumlahkan — kalau
ditambahkan, hari bolong terhitung dua kali.

Setiap email yang dikirim selalu dijawab satu baris, urutannya mengikuti urutan
`emails[]` yang dikirim. Email yang tidak punya akun Daily dijawab dengan angka nol,
bukan `404` — jadi payroll tidak perlu menangani email yang hilang secara khusus.

## Aturan perhitungan

### Hari telat

Tidak berubah. Ditandai saat laporan disimpan, ketika laporan dikirim setelah pukul
21:00 waktu Jakarta dan lemburnya tidak menutupi jam itu.

Catatan yang perlu diketahui payroll: **laporan rapel tidak dihitung telat.** Kalau
pegawai mengisi laporan tanggal kemarin pada besok paginya, patokannya adalah jam saat
dia menekan simpan, bukan tanggal laporannya. Jam 09:00 bukan lewat 21:00, jadi tidak
kena sanksi telat — dan karena laporannya ada, harinya juga bukan hari bolong. Ini
keputusan yang disengaja, bukan celah yang terlewat.

### Hari bolong

Hari kerja yang sama sekali tidak punya laporan. Yang **tidak** dihitung bolong:

| Kondisi | Alasan |
|---|---|
| Akhir pekan sesuai `work_schedule` pegawai | 5 hari kerja: Sabtu dan Minggu. 6 hari kerja: Minggu saja. |
| Libur nasional / cuti bersama | Dari tabel `holidays` di Daily. |
| Hari cuti, sakit, atau izin | Dari data ketidakhadiran — termasuk yang dikirim HRIS lewat endpoint sinkron cuti. |
| Hari berjalan | Belum jatuh tempo. Perhitungan berhenti di kemarin, walau sudah lewat pukul 21:00. |
| Hari sebelum akun Daily dibuat | Pegawai baru tidak ditagih laporan untuk masa sebelum dia punya akun. |

### Siapa yang kena

Hanya **Leader dan Staff yang bukan Security**. Sama persis dengan aturan sanksi telat
yang sudah berlaku sejak awal. Owner, Manager, dan Security selalu dijawab nol.

## Batasan yang perlu disepakati

Daily tidak menyimpan tanggal resign. Kalau payroll mengirim email pegawai yang sudah
keluar di tengah periode, hari kerja setelah tanggal keluarnya tetap terhitung bolong,
karena Daily tidak punya cara tahu.

Sampai ada kolom tanggal keluar, **sisi HRIS yang menyaring**: kirim hanya email
pegawai yang masih aktif sepanjang periode yang ditanyakan, atau potong `end` di
tanggal keluarnya untuk pegawai yang resign.

## Kode error

### 403 — secret salah atau tidak dikirim

```json
{ "success": false, "message": "Request internal tidak valid." }
```

### 422 — parameter tidak valid

Format validasi Laravel biasa (`errors` per field). Penyebab paling umum: `end`
sebelum `start`, atau `emails[]` dikirim sebagai string biasa, bukan array.

## Contoh pemanggilan

```bash
curl -G \
  -H "X-Internal-Secret: $DAILY_INTERNAL_SECRET" \
  -H "Accept: application/json" \
  --data-urlencode "start=2026-09-01" \
  --data-urlencode "end=2026-09-30" \
  --data-urlencode "emails[]=budi@bejogja.com" \
  --data-urlencode "emails[]=siti@bejogja.com" \
  "https://daily.awass.site/api/internal/payroll/daily-report-late"
```

```php
// Sisi HRIS (Laravel)
$response = Http::withHeaders([
        'X-Internal-Secret' => config('services.daily.secret'),
        'Accept' => 'application/json',
    ])
    ->get(config('services.daily.url').'/api/internal/payroll/daily-report-late', [
        'start' => $period->start->toDateString(),
        'end' => $period->end->toDateString(),
        'emails' => $employees->pluck('email')->all(),
    ]);

$sanksiPerEmail = collect($response->json('data'))
    ->keyBy('email')
    ->map(fn ($row) => $row['late_days']);
```

## Sisi Daily

- Controller: `app/Http/Controllers/Api/InternalPayrollDailyReportController.php`
- Rute: `routes/api.php`, nama `api.internal.payroll.daily-report-late`
- Test: `tests/Feature/InternalPayrollDailyReportApiTest.php`
