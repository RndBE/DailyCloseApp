<?php

namespace App\Support;

/**
 * Menyimpan "perusahaan aktif" untuk request yang sedang berjalan.
 *
 * - User biasa  : selalu di-set ke company_id miliknya (terkunci).
 * - Super-admin global : di-set ke perusahaan yang dipilih lewat switcher.
 * - Konsol / belum login : tidak di-set (id() = null) → global scope TIDAK memfilter,
 *   sehingga migrasi, seeder, dan proses login tetap berjalan normal.
 */
class CompanyContext
{
    protected static ?int $companyId = null;

    /** Set perusahaan aktif untuk request ini. */
    public static function set(?int $companyId): void
    {
        static::$companyId = $companyId;
    }

    /** ID perusahaan aktif, atau null bila tidak ada filter (mis. konsol). */
    public static function id(): ?int
    {
        return static::$companyId;
    }

    /** Apakah query harus difilter per perusahaan? */
    public static function hasFilter(): bool
    {
        return static::$companyId !== null;
    }

    public static function clear(): void
    {
        static::$companyId = null;
    }
}
