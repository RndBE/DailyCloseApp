<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // manual = dicatat sendiri lewat halaman Cuti.
            // absensi = hasil sinkron pengajuan yang di-ACC di HRIS.
            $table->string('source', 20)->default('manual')->after('reason');

            // ID pengajuan di sistem sumber. Dipakai agar sinkron idempotent:
            // approve ulang memperbarui baris yang sama, bukan menambah baris baru.
            $table->string('external_id', 64)->nullable()->after('source');

            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropUnique(['source', 'external_id']);
            $table->dropColumn(['source', 'external_id']);
        });
    }
};
