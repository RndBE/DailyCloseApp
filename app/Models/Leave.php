<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Leave extends Model
{
    use BelongsToCompany;

    public const TYPE_CUTI  = 'cuti';
    public const TYPE_SAKIT = 'sakit';
    public const TYPE_IZIN  = 'izin';

    public const TYPES = [
        self::TYPE_CUTI  => 'Cuti',
        self::TYPE_SAKIT => 'Sakit',
        self::TYPE_IZIN  => 'Izin',
    ];

    /**
     * Warna dan ikon badge per jenis.
     *
     * Ditaruh di model, bukan di masing-masing view: sebelumnya setiap tampilan
     * memakai percabangan biner "sakit atau bukan", sehingga jenis ketiga diam-diam
     * ikut memakai gaya cuti. Satu sumber membuat penambahan jenis berikutnya cukup
     * disentuh di sini.
     */
    private const BADGES = [
        self::TYPE_CUTI  => ['bg' => '#e5f4fb', 'color' => '#0c6f97', 'icon' => 'bi-calendar-heart'],
        self::TYPE_SAKIT => ['bg' => '#fdecec', 'color' => '#b02a37', 'icon' => 'bi-thermometer-half'],
        self::TYPE_IZIN  => ['bg' => '#f3eefc', 'color' => '#6b46c1', 'icon' => 'bi-envelope-paper'],
    ];

    /** Dicatat sendiri oleh karyawan lewat halaman Cuti. */
    public const SOURCE_MANUAL = 'manual';

    /** Hasil sinkron pengajuan yang sudah di-ACC di HRIS/absensi. */
    public const SOURCE_ABSENSI = 'absensi';

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'source',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** @return array{bg: string, color: string, icon: string} */
    public function getBadgeAttribute(): array
    {
        return self::BADGES[$this->type] ?? self::BADGES[self::TYPE_CUTI];
    }

    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Baris hasil sinkron HRIS — tidak boleh diubah/dihapus dari sisi Daily. */
    public function isSynced(): bool
    {
        return $this->source !== self::SOURCE_MANUAL;
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source === self::SOURCE_ABSENSI ? 'HRIS' : 'Manual';
    }

    /**
     * Cuti/sakit yang rentangnya beririsan dengan [$start, $end].
     *
     * Memakai whereDate, bukan where biasa: MySQL menyimpan kolom ini sebagai DATE
     * murni, tapi SQLite (dipakai test) menyimpannya sebagai '2026-07-13 00:00:00'.
     * Perbandingan string apa adanya membuat rentang sehari — kasus paling umum —
     * luput di SQLite, sehingga test bisa lolos/gagal berbeda dari produksi.
     */
    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }

    /**
     * Peta ketidakhadiran per tanggal untuk sekumpulan user dalam rentang.
     * Hasil: [user_id => ['Y-m-d' => Leave, ...], ...].
     */
    public static function dateMapForUsers(iterable $userIds, string $start, string $end): Collection
    {
        $startC = Carbon::parse($start);
        $endC   = Carbon::parse($end);

        return static::query()
            ->whereIn('user_id', $userIds)
            ->overlapping($start, $end)
            ->get()
            ->groupBy('user_id')
            ->map(function ($leaves) use ($startC, $endC) {
                $byDate = [];
                foreach ($leaves as $leave) {
                    $from = $leave->start_date->greaterThan($startC) ? $leave->start_date->copy() : $startC->copy();
                    $to   = $leave->end_date->lessThan($endC) ? $leave->end_date->copy() : $endC->copy();
                    for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                        $byDate[$d->toDateString()] = $leave;
                    }
                }
                return $byDate;
            });
    }
}
