<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel yang menjadi milik salah satu perusahaan (multi-tenant).
     * `holidays` sengaja tidak masuk: libur nasional dibagi untuk semua perusahaan.
     */
    private array $tables = [
        'users',
        'daily_reports',
        'leaves',
        'security_schedules',
        'report_comments',
        'comment_notifications',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                // Nullable: super-admin global (lintas perusahaan) bernilai NULL.
                $t->foreignId('company_id')->nullable()->after('id')
                    ->constrained('companies')->nullOnDelete();
            });
        }

        // Backfill: seluruh data lama menjadi milik perusahaan utama (ID 1).
        foreach ($this->tables as $table) {
            DB::table($table)->whereNull('company_id')->update(['company_id' => 1]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('company_id');
            });
        }
    }
};
