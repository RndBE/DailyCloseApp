<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;

/**
 * Membuat sebuah model menjadi milik salah satu perusahaan (multi-tenant).
 *
 * 1. Global scope: setiap query otomatis difilter ke perusahaan aktif
 *    (lihat App\Support\CompanyContext). Tanpa konteks → tidak difilter.
 * 2. Saat membuat record baru, company_id otomatis diisi dari perusahaan aktif
 *    bila belum ditentukan — mencegah data "bocor" tanpa pemilik.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new class implements Scope {
            public function apply(Builder $builder, Model $model): void
            {
                if (CompanyContext::hasFilter()) {
                    $builder->where($model->getTable() . '.company_id', CompanyContext::id());
                }
            }
        });

        static::creating(function (Model $model) {
            // Hanya isi otomatis bila company_id tidak disertakan sama sekali.
            // company_id yang di-set eksplisit (termasuk null untuk "Global")
            // dihormati apa adanya.
            if (! array_key_exists('company_id', $model->getAttributes()) && CompanyContext::hasFilter()) {
                $model->company_id = CompanyContext::id();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Jalankan query tanpa filter perusahaan (untuk kebutuhan lintas perusahaan). */
    public static function withoutCompanyScope(): Builder
    {
        return static::query()->withoutGlobalScopes();
    }
}
