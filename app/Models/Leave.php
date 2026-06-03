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

    public const TYPES = [
        self::TYPE_CUTI  => 'Cuti',
        self::TYPE_SAKIT => 'Sakit',
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
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

    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Cuti/sakit yang rentangnya beririsan dengan [$start, $end]. */
    public function scopeOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);
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
