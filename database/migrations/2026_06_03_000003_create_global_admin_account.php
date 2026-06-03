<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    private string $email = 'adminglobal@daily.site';

    /**
     * Membuat satu akun super-admin GLOBAL (company_id null) yang dapat
     * mengelola semua perusahaan. Idempotent: hanya dibuat bila belum ada,
     * sehingga aman dijalankan ulang dan otomatis ikut terpasang saat deploy.
     */
    public function up(): void
    {
        if (DB::table('users')->where('email', $this->email)->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name'              => 'Admin Global',
            'email'             => $this->email,
            'password'          => Hash::make('password'),
            'company_id'        => null,   // null = global / lintas perusahaan
            'level'             => 1,      // Owner
            'is_super_admin'    => true,
            'division'          => null,
            'managed_divisions' => null,
            'position'          => null,
            'is_active'         => true,
            'work_schedule'     => '5_days',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', $this->email)->delete();
    }
};
