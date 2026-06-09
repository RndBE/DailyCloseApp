<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\SecuritySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DailyReportSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_summary_groups_submitted_report_content_by_sender(): void
    {
        $owner = $this->makeUser([
            'name' => 'Owner Rangkuman',
            'email' => 'owner.rangkuman@example.test',
            'level' => User::LEVEL_OWNER,
            'division' => 'Direktur',
            'position' => 'Owner',
        ]);

        $agus = $this->makeUser([
            'name' => 'Agus Security',
            'email' => 'agus.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $bayu = $this->makeUser([
            'name' => 'Bayu Security',
            'email' => 'bayu.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $this->createReportFor($agus, [
            'completed_work' => 'Patroli pagar timur',
            'tomorrow_plan' => 'Cek akses loading dock',
        ]);
        $this->createWorkScheduleFor($agus, '18:00:00', '06:00:00');

        $this->createReportFor($bayu, [
            'completed_work' => 'Monitoring CCTV lobi',
            'tomorrow_plan' => 'Jaga pos depan',
        ]);
        $this->createWorkScheduleFor($bayu, '06:00:00', '18:00:00');

        $this->actingAs($owner)
            ->get(route('daily-reports.rangkuman', ['date' => '2026-06-04']))
            ->assertOk()
            ->assertSeeInOrder([
                'Divisi Security',
                'Agus Security',
                'Shift 18:00 - 06:00',
                'Patroli pagar timur',
                'Cek akses loading dock',
                'Bayu Security',
                'Shift 06:00 - 18:00',
                'Monitoring CCTV lobi',
                'Jaga pos depan',
            ]);
    }

    public function test_printable_security_summary_groups_submitted_report_content_by_sender(): void
    {
        $owner = $this->makeUser([
            'name' => 'Owner Cetak',
            'email' => 'owner.cetak@example.test',
            'level' => User::LEVEL_OWNER,
            'division' => 'Direktur',
            'position' => 'Owner',
        ]);

        $agus = $this->makeUser([
            'name' => 'Agus Cetak Security',
            'email' => 'agus.cetak.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $bayu = $this->makeUser([
            'name' => 'Bayu Cetak Security',
            'email' => 'bayu.cetak.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $this->createReportFor($agus, [
            'completed_work' => 'Patroli area parkir',
            'tomorrow_plan' => 'Cek radio komunikasi',
        ]);
        $this->createWorkScheduleFor($agus, '18:00:00', '06:00:00');

        $this->createReportFor($bayu, [
            'completed_work' => 'Serah terima shift malam',
            'tomorrow_plan' => 'Monitoring pintu belakang',
        ]);
        $this->createWorkScheduleFor($bayu, '06:00:00', '18:00:00');

        $this->actingAs($owner)
            ->get(route('daily-reports.rangkuman.cetak', ['date' => '2026-06-04']))
            ->assertOk()
            ->assertSeeInOrder([
                'Divisi Security',
                'Agus Cetak Security',
                'Shift 18:00 - 06:00',
                'Patroli area parkir',
                'Cek radio komunikasi',
                'Bayu Cetak Security',
                'Shift 06:00 - 18:00',
                'Serah terima shift malam',
                'Monitoring pintu belakang',
            ]);
    }

    public function test_security_summary_treats_scheduled_off_security_as_not_missing(): void
    {
        $owner = $this->makeUser([
            'name' => 'Owner Security Off',
            'email' => 'owner.security.off@example.test',
            'level' => User::LEVEL_OWNER,
            'division' => 'Direktur',
            'position' => 'Owner',
        ]);

        $workingSecurity = $this->makeUser([
            'name' => 'Dina Security',
            'email' => 'dina.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $offSecurity = $this->makeUser([
            'name' => 'Agung Security',
            'email' => 'agung.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $this->createReportFor($workingSecurity, [
            'completed_work' => 'Patroli area produksi',
        ]);

        $this->createOffScheduleFor($offSecurity);

        $response = $this->actingAs($owner)
            ->get(route('daily-reports.rangkuman', ['date' => '2026-06-04']))
            ->assertOk()
            ->assertSeeText('Libur Shift')
            ->assertSeeText('Agung Security')
            ->assertSeeText('1/1 anggota mengirim laporan');

        $this->assertStringNotContainsString(
            'Belum mengirim laporan: Agung Security',
            preg_replace('/\s+/', ' ', $response->getContent())
        );
    }

    public function test_printable_security_summary_treats_scheduled_off_security_as_not_missing(): void
    {
        $owner = $this->makeUser([
            'name' => 'Owner Security Off Cetak',
            'email' => 'owner.security.off.cetak@example.test',
            'level' => User::LEVEL_OWNER,
            'division' => 'Direktur',
            'position' => 'Owner',
        ]);

        $workingSecurity = $this->makeUser([
            'name' => 'Eka Security',
            'email' => 'eka.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $offSecurity = $this->makeUser([
            'name' => 'Agung Cetak Security',
            'email' => 'agung.cetak.security@example.test',
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);

        $this->createReportFor($workingSecurity, [
            'completed_work' => 'Monitoring akses utama',
        ]);

        $this->createOffScheduleFor($offSecurity);

        $response = $this->actingAs($owner)
            ->get(route('daily-reports.rangkuman.cetak', ['date' => '2026-06-04']))
            ->assertOk()
            ->assertSeeText('Libur Shift')
            ->assertSeeText('Agung Cetak Security')
            ->assertSeeText('1/1 laporan masuk');

        $this->assertStringNotContainsString(
            'Belum mengirim laporan: Agung Cetak Security',
            preg_replace('/\s+/', ' ', $response->getContent())
        );
    }

    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'company_id' => 1,
            'name' => 'Staff Rangkuman',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'level' => User::LEVEL_STAFF,
            'division' => 'Software',
            'position' => 'Staff',
            'is_active' => true,
            'work_schedule' => User::SCHEDULE_5DAYS,
        ], $attributes));
    }

    private function createReportFor(User $user, array $attributes = []): DailyReport
    {
        return DailyReport::create(array_merge([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'report_date' => '2026-06-04',
            'overtime_status' => false,
            'overtime_start' => null,
            'overtime_end' => null,
            'completed_work' => 'Mengerjakan laporan harian',
            'unfinished_work' => null,
            'obstacles' => null,
            'need_leader_help' => false,
            'leader_help_description' => null,
            'tomorrow_plan' => 'Melanjutkan pekerjaan',
            'work_finished_at' => '17:00',
            'additional_notes' => null,
        ], $attributes));
    }

    private function createOffScheduleFor(User $user): SecuritySchedule
    {
        return SecuritySchedule::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'date' => '2026-06-04',
            'start_time' => null,
            'end_time' => null,
            'is_off' => true,
            'is_manual' => false,
        ]);
    }

    private function createWorkScheduleFor(User $user, string $startTime, string $endTime): SecuritySchedule
    {
        return SecuritySchedule::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'date' => '2026-06-04',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_off' => false,
            'is_manual' => false,
        ]);
    }
}
