<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecuritySchedule extends Model
{
    use BelongsToCompany;

    /**
     * Pilihan shift yang valid: kode => detail.
     * Shift malam (night12 / s3) melewati tengah malam (end < start).
     */
    public const SHIFT_OPTIONS = [
        'off'     => ['label' => 'Libur',             'start' => null,       'end' => null,       'is_off' => true],
        'day12'   => ['label' => '06:00–18:00 (12j)', 'start' => '06:00:00', 'end' => '18:00:00', 'is_off' => false],
        'night12' => ['label' => '18:00–06:00 (12j)', 'start' => '18:00:00', 'end' => '06:00:00', 'is_off' => false],
        's1'      => ['label' => '06:00–14:00',       'start' => '06:00:00', 'end' => '14:00:00', 'is_off' => false],
        's2'      => ['label' => '14:00–22:00',       'start' => '14:00:00', 'end' => '22:00:00', 'is_off' => false],
        's3'      => ['label' => '22:00–06:00',       'start' => '22:00:00', 'end' => '06:00:00', 'is_off' => false],
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'date',
        'start_time',
        'end_time',
        'is_off',
        'is_manual',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'is_off'    => 'boolean',
            'is_manual' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Label shift singkat untuk ditampilkan, mis. "06:00 – 18:00". */
    public function getShiftLabelAttribute(): string
    {
        if ($this->is_off) {
            return 'Libur';
        }

        if (! $this->start_time || ! $this->end_time) {
            return '—';
        }

        return substr($this->start_time, 0, 5) . ' – ' . substr($this->end_time, 0, 5);
    }

    /** Jam kerja normal per hari (di atas ini dihitung lembur). */
    public const NORMAL_HOURS = 8;

    /** Durasi shift dalam jam (memperhitungkan shift yang melewati tengah malam). */
    public function durationHours(): float
    {
        if ($this->is_off || ! $this->start_time || ! $this->end_time) {
            return 0;
        }

        $start = strtotime('1970-01-01 ' . $this->start_time);
        $end   = strtotime('1970-01-01 ' . $this->end_time);
        if ($end <= $start) {
            $end += 86400; // shift malam menyeberang ke hari berikutnya
        }

        return ($end - $start) / 3600;
    }

    /** Jam lembur = kelebihan durasi shift di atas jam kerja normal. */
    public function overtimeHours(): float
    {
        return max(0, $this->durationHours() - self::NORMAL_HOURS);
    }

    /** Kode shift (off/day12/.../s3) yang cocok dengan jam tersimpan. */
    public function shiftCode(): string
    {
        if ($this->is_off) {
            return 'off';
        }

        foreach (self::SHIFT_OPTIONS as $code => $opt) {
            if ($opt['is_off']) {
                continue;
            }
            if (substr((string) $this->start_time, 0, 5) === substr((string) $opt['start'], 0, 5)
                && substr((string) $this->end_time, 0, 5) === substr((string) $opt['end'], 0, 5)) {
                return $code;
            }
        }

        return 'off';
    }
}
