# API Internal — Sinkron Cuti/Sakit dari HRIS ke Daily

Endpoint service-to-service supaya pengajuan cuti/sakit yang **sudah di-ACC di HRIS**
(`backend_absensi`) otomatis muncul di tampilan cuti/sakit Daily, tanpa karyawan
perlu mencatat ulang.

Arahnya **push dari HRIS ke Daily**: HRIS pemilik proses approval, Daily hanya
menerima hasil akhirnya. Daily tidak pernah mengintip tabel `leave_requests`.

Begitu satu baris masuk, seluruh tampilan Daily ikut terisi sendiri — halaman Cuti,
grid laporan bulanan, rangkuman harian, badge di daftar laporan — dan karyawan yang
bersangkutan otomatis **tidak dihitung belum lapor** dan **tidak kena `is_late`**
pada tanggal tersebut.

## Endpoint

### 1. Catat / perbarui (saat pengajuan di-ACC)

```
POST {DAILY_APP_URL}/api/internal/leaves/sync
```

### 2. Hapus (saat ACC dibatalkan atau berubah jadi ditolak)

```
DELETE {DAILY_APP_URL}/api/internal/leaves/{external_id}
```

### Header wajib

```
X-Internal-Secret: <nilai DAILY_INTERNAL_SECRET>
Accept: application/json
```

Nilai secret ini sudah ada di `.env` HRIS sebagai `DAILY_INTERNAL_SECRET`, dan harus
sama dengan `ABSENSI_BRIDGE_SECRET` di `.env` Daily. Header yang salah/kosong dijawab
`403`. Kalau secret di sisi Daily kosong, semua request ditolak — gagal tertutup,
bukan diloloskan. Jangan tulis nilainya di repo, tiket, atau chat grup.

## Payload `sync`

| Field | Wajib | Keterangan |
|---|---|---|
| `external_id` | ya | ID pengajuan di HRIS, maks 64 karakter. Pakai `leave_requests.id`. Ini kunci idempotensi. |
| `email` | ya | Email pegawai. Tidak membedakan huruf besar/kecil. |
| `type` | ya | Hanya `cuti` atau `sakit`. Nilai lain dijawab `422`. |
| `start_date` | ya | `Y-m-d` |
| `end_date` | ya | `Y-m-d`, tidak boleh sebelum `start_date` |
| `reason` | tidak | Maks 500 karakter. Kirim ringkasannya saja — jangan detail diagnosis medis. |

```json
{
  "external_id": "91",
  "email": "budi@bejogja.com",
  "type": "sakit",
  "start_date": "2026-08-11",
  "end_date": "2026-08-13",
  "reason": "Surat dokter"
}
```

## Pemetaan jenis izin HRIS → `type` Daily

Daily hanya kenal dua jenis, karena fungsinya cuma satu: menandai hari yang **tidak
wajib lapor harian**. Jadi yang dikirim hanya izin yang benar-benar membuat orangnya
tidak bekerja sehari penuh.

Daftar berikut disamakan dengan isi tabel `leave_types` yang benar-benar ada di DB
HRIS (bukan dari seeder — nama di seeder sudah tidak sama dengan data nyata):

| `leave_types.id` | `leave_types.name` | Kirim ke Daily |
|---|---|---|
| 5 | Sakit | `sakit` |
| 1 | Cuti Tahunan | `cuti` |
| 4 | Cuti Melahirkan | `cuti` |
| 3 | Izin Datang Terlambat | **jangan dikirim** |
| 6 | Work From Home | **jangan dikirim** |

Petakan lewat **nama yang dinormalisasi** (lowercase + trim), bukan `leave_type_id`,
supaya tidak ikut rusak kalau tabel di-seed ulang dengan id berbeda. Catatan: id 2
sudah tidak ada, jadi id memang tidak berurutan.

Dua baris terakhir itu penting. Izin parsial dan WFH tetap dihitung hadir dan orangnya
tetap kerja, jadi **tetap wajib mengisi laporan harian**. Kalau ikut dikirim, mereka
hilang dari daftar "belum lapor" padahal seharusnya masih ditagih.

Kalau nanti ada leave type "Izin Pulang Cepat" (belum ada di DB sekarang), perlakuannya
sama seperti Izin Datang Terlambat: jangan dikirim.

Kalau nanti HRIS menambah leave type baru, defaultnya **jangan dikirim** sampai
disepakati masuk kolom mana. Aman by default.

## Kapan dipanggil

Titik pasangnya sudah ada di HRIS — hook `booted()` di `app/Models/LeaveRequest.php`,
yang sekarang dipakai `AttendanceLeaveSync`. Hook itu sudah menyala persis pada dua
momen yang dibutuhkan:

| Kondisi di hook | Panggil |
|---|---|
| `status` berubah menjadi `approved` | `POST /sync` |
| sebelumnya `approved`, kini bukan (ditolak/dibatalkan) | `DELETE /{external_id}` |

Jadi tidak perlu menyisir semua jalur approve satu per satu — cukup satu tempat, sama
seperti `AttendanceLeaveSync::apply()` / `revert()`.

Tanggal pengajuan yang direvisi lalu di-ACC ulang juga aman: kirim `sync` lagi dengan
`external_id` yang sama.

## Idempotensi

`external_id` unik per sumber di sisi Daily. Kirim `sync` dua kali dengan
`external_id` sama → baris yang sama **diperbarui**, bukan dobel (`created: false`).
Kirim `DELETE` dua kali → tetap `200` dengan `deleted: false`, bukan error.

Aman untuk di-retry.

## Contoh response

### 201 — baru dicatat

```json
{
  "success": true,
  "created": true,
  "message": "Cuti/sakit berhasil dicatat di Daily.",
  "data": {
    "id": 148,
    "external_id": "91",
    "email": "budi@bejogja.com",
    "type": "sakit",
    "start_date": "2026-08-11",
    "end_date": "2026-08-13",
    "days_count": 3
  },
  "overlapping_manual_ids": []
}
```

### 200 — sudah ada, diperbarui

Bentuknya sama, `created: false`.

`overlapping_manual_ids` berisi ID catatan yang sebelumnya **diisi manual** oleh
karyawan pada rentang yang beririsan. Sinkron **tidak** menghapusnya — itu data milik
karyawan, bukan milik proses ini. Isi array ini cuma penanda buat HRD kalau mau
merapikan. Tampilan Daily sendiri tidak rusak karena duplikat: grid bulanan
memetakan per tanggal, jadi satu hari tetap satu penanda.

### 404 — pegawai tidak punya akun Daily

```json
{
  "success": false,
  "message": "Akun Daily dengan email tersebut belum terdaftar atau sudah nonaktif."
}
```

Wajar dan tidak perlu dianggap kegagalan sistem: tidak semua pegawai HRIS punya akun
Daily. Cukup dicatat di log, jangan di-retry terus-menerus.

### 422 — payload tidak valid

Format validasi Laravel biasa (`errors` per field). Penyebab paling umum: `type` bukan
`cuti`/`sakit` — biasanya karena leave type baru belum dipetakan.

### 403 — secret salah atau tidak dikirim

```json
{ "success": false, "message": "Request internal tidak valid." }
```

## Contoh pemanggilan

```bash
curl -X POST \
  -H "X-Internal-Secret: $DAILY_INTERNAL_SECRET" \
  -H "Accept: application/json" \
  -d external_id=91 -d email=budi@bejogja.com -d type=sakit \
  -d start_date=2026-08-11 -d end_date=2026-08-13 \
  "https://daily.awass.site/api/internal/leaves/sync"
```

```php
// Sisi HRIS (Laravel) — pola sama dengan DailyTokenController yang sudah ada.
$response = Http::withHeaders(['X-Internal-Secret' => config('services.daily.internal_secret')])
    ->acceptJson()
    ->connectTimeout(3)
    ->timeout(10)
    ->post(rtrim(config('services.daily.url'), '/') . '/api/internal/leaves/sync', [
        'external_id' => (string) $leave->id,
        'email'       => $leave->employee->email,
        'type'        => $typeDaily,          // hasil pemetaan tabel di atas
        'start_date'  => $leave->start_date->toDateString(),
        'end_date'    => $leave->end_date->toDateString(),
        'reason'      => $leave->reason,
    ]);
```

Kalau `services.daily.verify_ssl` false, ikutkan `->withoutVerifying()` seperti di
`DailyTokenController`.

## Keandalan

Jalankan push-nya lewat **queue job**, jangan inline di dalam request approve. Alasannya:
approval tidak boleh gagal atau menggantung hanya karena Daily sedang tidak bisa
dihubungi. Job-nya boleh di-retry bebas — endpointnya idempotent.

Untuk menambal yang tetap lolos (server mati saat job jalan, dsb.), sediakan satu
artisan command yang menyisir `leave_requests` berstatus `approved` dalam N hari
terakhir dan mengirim ulang semuanya, lalu jadwalkan harian. Karena idempotent, kirim
ulang yang sudah tercatat tidak menimbulkan efek samping.

## Prasyarat data

Pencocokan orang memakai **email**, pola yang sama dengan endpoint
`/api/internal/mobile-token` yang sudah dipakai HRIS. Jadi email di HRIS dan di Daily
harus sama (besar/kecil huruf tidak masalah). Pegawai yang emailnya beda, atau belum
punya akun Daily, akan dijawab `404` dan cuti-nya tidak tercatat di Daily — perlu
dirapikan lewat data pegawai, bukan lewat kode.

## Yang berubah di sisi Daily

- Tabel `leaves` dapat kolom `source` (`manual` | `absensi`) dan `external_id`.
  Catatan lama otomatis jadi `manual`.
- Baris hasil sinkron ditandai `HRIS` di halaman Cuti dan **tidak bisa dihapus
  karyawan** — pembatalannya harus lewat HRIS, supaya kedua sistem tidak beda isi.
- Form "Catat Cuti / Sakit" manual tetap ada sebagai jalur darurat (mis. pegawai belum
  terdaftar di HRIS).

## Berkas terkait

- Route: `routes/api.php` (`api.internal.leaves.sync`, `api.internal.leaves.revoke`)
- Controller: `app/Http/Controllers/Api/InternalLeaveSyncController.php`
- Model: `app/Models/Leave.php` (konstanta `SOURCE_MANUAL`, `SOURCE_ABSENSI`)
- Migrasi: `database/migrations/2026_08_10_000001_add_source_to_leaves_table.php`
- Test: `tests/Feature/InternalLeaveSyncApiTest.php`
- Config: `config/services.php` → `services.absensi_bridge.secret`
