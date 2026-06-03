<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Dua perusahaan yang memakai aplikasi ini. ID 1 = perusahaan utama
        // (semua data lama di-backfill ke sini pada migrasi berikutnya).
        DB::table('companies')->insert([
            ['id' => 1, 'name' => 'PT Arta Teknologi Comunindo', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'CV Arta Solusindo', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
