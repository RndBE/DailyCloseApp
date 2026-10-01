<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecalculateDailyReportLateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_changes_nothing_and_apply_uses_the_rule_of_each_level(): void
    {
        $staff = $this->makeUser('staff@example.test', User::LEVEL_STAFF);
        $manager = $this->makeUser('manager@example.test', User::LEVEL_MANAGER);
        $security = $this->makeUser('satpam@example.test', User::LEVEL_STAFF, User::SCHEDULE_SECURITY);

        // Staff, dikirim 21:07 hari yang sama: tetap telat.
        $sameNight = $this->makeReport($staff, '2026-09-05', '2026-09-05 21:07:00', isLate: true);
        // Staff, dikirim 21:30 tapi tersimpan tidak telat (sempat memakai aturan Manager): jadi telat.
        $wrongRule = $this->makeReport($staff, '2026-09-30', '2026-09-30 21:30:00', isLate: false);
        // Staff, laporan susulan pagi hari enam hari kemudian: aturan lama meloloskan, sekarang telat.
        $weekLate = $this->makeReport($staff, '2026-09-25', '2026-10-01 09:32:00', isLate: false);
        // Manager: dikirim malam hari itu tepat waktu, dikirim besok jam 11:00 telat.
        $managerNight = $this->makeReport($manager, '2026-09-09', '2026-09-09 21:07:00', isLate: false);
        $managerLate = $this->makeReport($manager, '2026-09-10', '2026-09-11 11:00:00', isLate: false);
        // Security dan hari cuti tetap bebas sanksi.
        $securityLate = $this->makeReport($security, '2026-09-10', '2026-09-12 11:00:00', isLate: false);
        $onLeave = $this->makeReport($staff, '2026-09-14', '2026-09-16 11:00:00', isLate: false);
        Leave::withoutGlobalScopes()->create([
            'company_id' => 1,
            'user_id' => $staff->id,
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-14',
            'source' => Leave::SOURCE_MANUAL,
        ]);
        // Di luar rentang: tidak disentuh.
        $august = $this->makeReport($staff, '2026-08-28', '2026-08-29 09:00:00', isLate: false);

        $this->artisan('daily-reports:recalculate-late', ['--from' => '2026-09-01', '--to' => '2026-09-30'])
            ->assertSuccessful();
        $this->assertFalse($weekLate->fresh()->is_late);
        $this->assertFalse($wrongRule->fresh()->is_late);

        $updatedBefore = $weekLate->fresh()->updated_at->toDateTimeString();

        $this->artisan('daily-reports:recalculate-late', ['--from' => '2026-09-01', '--to' => '2026-09-30', '--apply' => true])
            ->assertSuccessful();

        $this->assertTrue($sameNight->fresh()->is_late);
        $this->assertTrue($wrongRule->fresh()->is_late);
        $this->assertTrue($weekLate->fresh()->is_late);
        $this->assertFalse($managerNight->fresh()->is_late);
        $this->assertTrue($managerLate->fresh()->is_late);
        $this->assertFalse($securityLate->fresh()->is_late);
        $this->assertFalse($onLeave->fresh()->is_late);
        $this->assertFalse($august->fresh()->is_late);
        $this->assertSame($updatedBefore, $weekLate->fresh()->updated_at->toDateTimeString());
    }

    private function makeReport(User $user, string $date, string $submittedAt, bool $isLate): DailyReport
    {
        $at = Carbon::parse($submittedAt, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        $report = DailyReport::withoutGlobalScopes()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'report_date' => $date,
            'completed_work' => 'Kerjaan selesai',
            'tomorrow_plan' => 'Lanjut besok',
            'work_finished_at' => '17:00',
            'is_late' => $isLate,
        ]);
        $report->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();

        return $report;
    }

    private function makeUser(string $email, int $level, string $schedule = User::SCHEDULE_6DAYS): User
    {
        return User::create([
            'company_id' => 1,
            'name' => 'User '.$email,
            'email' => $email,
            'password' => Hash::make('password'),
            'level' => $level,
            'division' => $schedule === User::SCHEDULE_SECURITY ? User::DIVISION_SECURITY : 'Software',
            'position' => 'Staff',
            'is_active' => true,
            'work_schedule' => $schedule,
        ]);
    }
}
