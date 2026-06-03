<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // company_id null = super-admin global (lintas perusahaan).
        // 1 = PT Arta Teknologi Comunindo, 2 = CV Arta Solusindo.
        $users = [
            [
                'name' => 'Admin Sistem',
                'email' => 'admin@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_OWNER,
                'is_super_admin' => true,
                'company_id' => null,
                'division' => 'Admin Project',
                'managed_divisions' => null,
                'position' => 'Manager',
                'is_active' => true,
            ],
            [
                'name' => 'Ahmad Direktur',
                'email' => 'direktur@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_OWNER,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Direktur',
                'managed_divisions' => null,
                'position' => 'Direktur',
                'is_active' => true,
            ],
            [
                'name' => 'Andi Pratama',
                'email' => 'manager@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_MANAGER,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Engineer',
                'managed_divisions' => ['Engineer', 'Hardware', 'Software'],
                'position' => 'Manager',
                'is_active' => true,
            ],
            [
                'name' => 'Dewi Kusuma',
                'email' => 'manager2@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_MANAGER,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Marketing',
                'managed_divisions' => ['Marketing', 'Publikasi'],
                'position' => 'Manager',
                'is_active' => true,
            ],
            [
                'name' => 'Sari Wijaya',
                'email' => 'leader@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_LEADER,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Engineer',
                'managed_divisions' => null,
                'position' => 'Leader',
                'is_active' => true,
            ],
            [
                'name' => 'Tono Hidayat',
                'email' => 'leader2@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_LEADER,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Marketing',
                'managed_divisions' => null,
                'position' => 'Leader',
                'is_active' => true,
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'staff@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_STAFF,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Engineer',
                'managed_divisions' => null,
                'position' => 'Staff',
                'is_active' => true,
            ],
            [
                'name' => 'Rina Hartati',
                'email' => 'rina@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_STAFF,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Software',
                'managed_divisions' => null,
                'position' => 'Staff',
                'is_active' => true,
            ],
            [
                'name' => 'Joko Susilo',
                'email' => 'helper@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_STAFF,
                'is_super_admin' => false,
                'company_id' => 1,
                'division' => 'Helper',
                'managed_divisions' => null,
                'position' => 'Staff',
                'is_active' => true,
            ],

            // --- CV Arta Solusindo (company_id = 2) — untuk uji isolasi data ---
            [
                'name' => 'Bagas Direktur',
                'email' => 'direktur.cv@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_OWNER,
                'is_super_admin' => false,
                'company_id' => 2,
                'division' => 'Direktur',
                'managed_divisions' => null,
                'position' => 'Direktur',
                'is_active' => true,
            ],
            [
                'name' => 'Citra Lestari',
                'email' => 'manager.cv@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_MANAGER,
                'is_super_admin' => false,
                'company_id' => 2,
                'division' => 'Marketing',
                'managed_divisions' => ['Marketing', 'Admin'],
                'position' => 'Manager',
                'is_active' => true,
            ],
            [
                'name' => 'Dani Saputra',
                'email' => 'staff.cv@daily.test',
                'password' => Hash::make('password'),
                'level' => User::LEVEL_STAFF,
                'is_super_admin' => false,
                'company_id' => 2,
                'division' => 'Marketing',
                'managed_divisions' => null,
                'position' => 'Staff',
                'is_active' => true,
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }

        $staffBudi = User::where('email', 'staff@daily.test')->first();
        $staffRina = User::where('email', 'rina@daily.test')->first();
        $staffJoko = User::where('email', 'helper@daily.test')->first();
        $staffDani = User::where('email', 'staff.cv@daily.test')->first();

        DailyReport::updateOrCreate(
            ['user_id' => $staffBudi->id, 'report_date' => now()->toDateString()],
            [
                'company_id' => 1,
                'overtime_status' => true,
                'overtime_start' => '17:00:00',
                'overtime_end' => '20:00:00',
                'completed_work' => "- Assembly panel telemetry 3 unit selesai\n- Wiring box kontrol selesai\n- QC relay dan power supply selesai",
                'unfinished_work' => '- Labeling kabel panel unit ke-4',
                'obstacles' => '- Stok terminal block menipis',
                'need_leader_help' => true,
                'leader_help_description' => '- Approval pembelian material',
                'tomorrow_plan' => "- Menyelesaikan panel unit ke-4\n- QC final dan packing",
                'work_finished_at' => '20:05:00',
                'additional_notes' => '- Lembur untuk mengejar target pengiriman',
            ]
        );

        DailyReport::updateOrCreate(
            ['user_id' => $staffRina->id, 'report_date' => now()->subDay()->toDateString()],
            [
                'company_id' => 1,
                'overtime_status' => false,
                'overtime_start' => null,
                'overtime_end' => null,
                'completed_work' => "- Deploy patch v1.2.4 ke staging\n- Review pull request modul invoice",
                'unfinished_work' => null,
                'obstacles' => null,
                'need_leader_help' => false,
                'leader_help_description' => null,
                'tomorrow_plan' => "- UAT modul invoice\n- Coding fitur export PDF",
                'work_finished_at' => '17:00:00',
                'additional_notes' => null,
            ]
        );

        DailyReport::updateOrCreate(
            ['user_id' => $staffJoko->id, 'report_date' => now()->toDateString()],
            [
                'company_id' => 1,
                'overtime_status' => false,
                'overtime_start' => null,
                'overtime_end' => null,
                'completed_work' => "- Bantu setup ruang meeting lantai 3\n- Distribusi ATK ke tiap divisi",
                'unfinished_work' => '- Pemeriksaan stok ATK gudang',
                'obstacles' => null,
                'need_leader_help' => false,
                'leader_help_description' => null,
                'tomorrow_plan' => "- Lanjut stock-take ATK\n- Setup ruang training",
                'work_finished_at' => '17:00:00',
                'additional_notes' => null,
            ]
        );

        // Laporan milik CV Arta Solusindo (company_id = 2) untuk uji isolasi.
        if ($staffDani) {
            DailyReport::updateOrCreate(
                ['user_id' => $staffDani->id, 'report_date' => now()->toDateString()],
                [
                    'company_id' => 2,
                    'overtime_status' => false,
                    'overtime_start' => null,
                    'overtime_end' => null,
                    'completed_work' => "- Follow up 5 leads marketing\n- Update konten katalog produk",
                    'unfinished_work' => '- Rekap penawaran mingguan',
                    'obstacles' => null,
                    'need_leader_help' => false,
                    'leader_help_description' => null,
                    'tomorrow_plan' => "- Closing penawaran klien A\n- Meeting tim marketing",
                    'work_finished_at' => '17:00:00',
                    'additional_notes' => null,
                ]
            );
        }
    }
}
