<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_date',
        'overtime_status',
        'overtime_start',
        'overtime_end',
        'completed_work',
        'unfinished_work',
        'obstacles',
        'need_leader_help',
        'leader_help_description',
        'tomorrow_plan',
        'work_finished_at',
        'additional_notes',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'overtime_status' => 'boolean',
            'need_leader_help' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Restrict reports to those the given user is allowed to see.
     *
     * Super Admin -> semua laporan
     * Owner (L1)  -> laporan level 2, 3, 4 (semua divisi)
     * Manager(L2) -> laporan level 3, 4 dari divisi yang dia bawahi (managed_divisions)
     * Leader (L3) -> laporan level 4 dari divisi yang sama
     * Staff  (L4) -> hanya laporan sendiri
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->level === User::LEVEL_OWNER) {
            return $query->whereHas('user', fn ($q) => $q->whereIn('level', [2, 3, 4]));
        }

        if ($user->level === User::LEVEL_MANAGER) {
            $divisions = $user->visibleDivisions() ?? [];

            return $query->whereHas('user', function ($q) use ($divisions) {
                $q->whereIn('level', [3, 4]);
                if (empty($divisions)) {
                    $q->whereRaw('1 = 0'); // manager tanpa divisi yg dibawahi → tidak lihat apa-apa
                } else {
                    $q->whereIn('division', $divisions);
                }
            });
        }

        if ($user->level === User::LEVEL_LEADER) {
            $divisions = $user->visibleDivisions() ?? [];

            return $query->whereHas('user', function ($q) use ($divisions) {
                $q->where('level', 4);
                if (empty($divisions)) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('division', $divisions);
                }
            });
        }

        return $query->where('user_id', $user->id);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        $owner = $this->user;
        if (! $owner) {
            return false;
        }

        return match ($user->level) {
            User::LEVEL_OWNER => in_array($owner->level, [2, 3, 4], true),
            User::LEVEL_MANAGER => in_array($owner->level, [3, 4], true)
                                   && in_array($owner->division, $user->visibleDivisions() ?? [], true),
            User::LEVEL_LEADER => $owner->level === 4
                                   && in_array($owner->division, $user->visibleDivisions() ?? [], true),
            default => false,
        };
    }
}
