<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use BelongsToCompany, HasFactory, Notifiable;

    public const LEVEL_OWNER = 1;

    public const LEVEL_MANAGER = 2;

    public const LEVEL_LEADER = 3;

    public const LEVEL_STAFF = 4;

    public const SCHEDULE_5DAYS = '5_days';

    public const SCHEDULE_6DAYS = '6_days';

    public const SCHEDULE_SECURITY = 'security';

    public const WORK_SCHEDULES = [
        self::SCHEDULE_5DAYS => '5 Hari Kerja',
        self::SCHEDULE_6DAYS => '6 Hari Kerja',
        self::SCHEDULE_SECURITY => 'Security (Shift)',
    ];

    public const WORK_SCHEDULE_DETAILS = [
        self::SCHEDULE_5DAYS => [
            'label' => '5 Hari Kerja',
            'weekdays' => 'Senin – Jumat',
            'hours' => '08:00 – 17:00',
            'saturday' => null,
        ],
        self::SCHEDULE_6DAYS => [
            'label' => '6 Hari Kerja',
            'weekdays' => 'Senin – Jumat',
            'hours' => '09:00 – 17:00',
            'saturday' => 'Sabtu: 09:00 – 14:00',
        ],
        self::SCHEDULE_SECURITY => [
            'label' => 'Security (Shift)',
            'weekdays' => 'Shift bergilir',
            'hours' => 'Jadwal diatur per tanggal',
            'saturday' => null,
        ],
    ];

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
        'Supporting Staff',
        self::DIVISION_SECURITY,
    ];

    /** Nama divisi security (dipakai untuk gating akses jadwal security). */
    public const DIVISION_SECURITY = 'Security';

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
        'company_id',
        'level',
        'is_super_admin',
        'division',
        'managed_divisions',
        'position',
        'is_active',
        'work_schedule',
        'is_outsourcing',
        'api_token_hash',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_outsourcing' => 'boolean',
            'level' => 'integer',
            'managed_divisions' => 'array',
        ];
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function securitySchedules(): HasMany
    {
        return $this->hasMany(SecuritySchedule::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function isSecurity(): bool
    {
        return $this->work_schedule === self::SCHEDULE_SECURITY;
    }

    /**
     * Security outsourcing: shift 12 jam tidak dihitung lembur otomatis 4 jam.
     * Hanya bermakna bila user adalah security.
     */
    public function isOutsourcing(): bool
    {
        return $this->isSecurity() && (bool) $this->is_outsourcing;
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

    /**
     * Super-admin global = lintas perusahaan (company_id null).
     * Hanya akun seperti ini yang melihat switcher perusahaan & bisa berpindah.
     */
    public function isGlobalAdmin(): bool
    {
        return $this->isSuperAdmin() && is_null($this->company_id);
    }

    /**
     * Route-model-binding tanpa company scope. Visibilitas (perusahaan aktif +
     * admin global) ditegakkan di UserController::assertCanManage, sehingga admin
     * global tetap bisa membuka/mengelola akun global (company_id null).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScopes()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Boleh mengatur jadwal security?
     * - Super Admin / Owner : ya (semua security)
     * - Manager / Leader    : ya, jika membawahi divisi Security (dibatasi ke divisinya)
     * - Lainnya             : tidak
     */
    public function canManageSecuritySchedule(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $visible = $this->visibleDivisions();

        return $visible === null || in_array(self::DIVISION_SECURITY, $visible, true);
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
