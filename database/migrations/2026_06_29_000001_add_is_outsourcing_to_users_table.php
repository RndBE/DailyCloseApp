<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Penanda security outsourcing: shift 12 jam tidak dihitung lembur otomatis 4 jam.
            $table->boolean('is_outsourcing')->default(false)->after('work_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_outsourcing');
        });
    }
};
