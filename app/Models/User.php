<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const LEVEL_OWNER = 1;

    public const LEVEL_MANAGER = 2;

    public const LEVEL_LEADER = 3;

    public const LEVEL_STAFF = 4;

    public const LEVEL_NAMES = [
        self::LEVEL_OWNER => 'Owner',
        self::LEVEL_MANAGER => 'Manager',
        self::LEVEL_LEADER => 'Leader',
        self::LEVEL_STAFF => 'Staff',
    ];

    /** Daftar divisi yang valid (urutan ditampilkan di dropdown). */
    public const DIVISIONS = [
        'Direktur',
        'Komisaris',
        'Marketing',
        'RnD',
        'Software',
        'Admin',
        'Admin Project',
        'Engineer',
        'Tax Officer',
        'Accounting',
        'Purchasing',
        'HSE',
        'Helper',
        'HRD',
        'Publikasi',
        'Hardware',
    ];

    /** Daftar jabatan yang valid. */
    public const POSITIONS = [
        'Direktur',
        'Komisaris',
        'Manager',
        'Leader',
        'Staff',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'level',
        'is_super_admin',
        'division',
        'managed_divisions',
        'position',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'level' => 'integer',
            'managed_divisions' => 'array',
        ];
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function getLevelNameAttribute(): string
    {
        return self::LEVEL_NAMES[$this->level] ?? 'Unknown';
    }

    public function getRoleLabelAttribute(): string
    {
        if ($this->is_super_admin) {
            return 'Super Admin';
        }

        return $this->level_name;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Daftar divisi yang relevan untuk filter visibility laporan.
     *
     * - Super Admin / Owner    : null  (tidak difilter berdasarkan divisi)
     * - Manager / Leader       : managed_divisions (fallback ke [division] jika kosong)
     * - Staff                  : tidak relevan (filter pakai user_id)
     *
     * @return array<int,string>|null Null artinya "tidak ada filter divisi"
     */
    public function visibleDivisions(): ?array
    {
        if ($this->isSuperAdmin() || $this->level === self::LEVEL_OWNER) {
            return null;
        }

        if (in_array($this->level, [self::LEVEL_MANAGER, self::LEVEL_LEADER], true)) {
            $managed = array_filter((array) $this->managed_divisions, fn ($d) => filled($d));
            if (! empty($managed)) {
                return array_values($managed);
            }

            return $this->division ? [$this->division] : [];
        }

        return [];
    }
}
