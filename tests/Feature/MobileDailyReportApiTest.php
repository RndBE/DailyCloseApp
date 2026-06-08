<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileDailyReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_returns_a_bearer_token_for_active_users(): void
    {
        $user = $this->makeStaffUser([
            'email' => 'staff@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => 'staff@example.test',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'division', 'position', 'level'],
            ]);

        $this->assertNotNull($user->fresh()->api_token_hash);
    }

    public function test_mobile_daily_report_requires_a_valid_bearer_token(): void
    {
        $response = $this->postJson('/api/mobile/daily-reports', $this->validReportPayload(), [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_mobile_user_can_submit_one_daily_report_per_date(): void
    {
        $user = $this->makeStaffUser();
        $token = $this->loginAndReturnToken($user);

        $response = $this->postJson('/api/mobile/daily-reports', $this->validReportPayload(), [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_date', '2026-06-04')
            ->assertJsonPath('data.completed_work', 'Mengerjakan integrasi mobile daily');

        $this->assertDatabaseHas('daily_reports', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'completed_work' => 'Mengerjakan integrasi mobile daily',
        ]);
        $this->assertSame('2026-06-04', DailyReport::first()->report_date->toDateString());

        $duplicate = $this->postJson('/api/mobile/daily-reports', $this->validReportPayload(), [
            'Authorization' => 'Bearer '.$token,
        ]);

        $duplicate->assertUnprocessable()
            ->assertJsonValidationErrors('report_date');
    }

    public function test_mobile_user_can_fetch_today_report(): void
    {
        $user = $this->makeStaffUser();
        $token = $this->loginAndReturnToken($user);

        DailyReport::create($this->validReportPayload() + [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ]);

        $response = $this->getJson('/api/mobile/daily-reports/today?date=2026-06-04', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report_date', '2026-06-04');
    }

    public function test_mobile_daily_report_submitted_after_nine_pm_is_marked_late(): void
    {
        Carbon::setTestNow('2026-06-04 21:15:00');

        $user = $this->makeStaffUser();
        $token = $this->loginAndReturnToken($user);

        $this->postJson('/api/mobile/daily-reports', $this->validReportPayload(), [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated()
            ->assertJsonPath('data.is_late', true);

        $this->assertDatabaseHas('daily_reports', [
            'user_id' => $user->id,
            'is_late' => true,
        ]);

        Carbon::setTestNow();
    }

    public function test_mobile_daily_report_with_overtime_until_after_nine_pm_is_not_marked_late(): void
    {
        Carbon::setTestNow('2026-06-04 21:15:00');

        $user = $this->makeStaffUser();
        $token = $this->loginAndReturnToken($user);
        $payload = array_merge($this->validReportPayload(), [
            'overtime_status' => true,
            'overtime_start' => '17:00',
            'overtime_end' => '21:30',
            'work_finished_at' => '21:30',
        ]);

        $this->postJson('/api/mobile/daily-reports', $payload, [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated()
            ->assertJsonPath('data.is_late', false);

        $this->assertDatabaseHas('daily_reports', [
            'user_id' => $user->id,
            'is_late' => false,
        ]);

        Carbon::setTestNow();
    }

    public function test_mobile_security_daily_report_submitted_after_nine_pm_is_not_marked_late(): void
    {
        Carbon::setTestNow('2026-06-04 21:15:00');

        $user = $this->makeStaffUser([
            'division' => User::DIVISION_SECURITY,
            'work_schedule' => User::SCHEDULE_SECURITY,
        ]);
        $token = $this->loginAndReturnToken($user);

        $this->postJson('/api/mobile/daily-reports', $this->validReportPayload(), [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated()
            ->assertJsonPath('data.is_late', false);

        $this->assertDatabaseHas('daily_reports', [
            'user_id' => $user->id,
            'is_late' => false,
        ]);

        Carbon::setTestNow();
    }

    public function test_mobile_superior_can_fetch_lower_level_reports_from_same_division(): void
    {
        $leader = $this->makeStaffUser([
            'name' => 'Software Leader',
            'email' => 'leader@example.test',
            'level' => User::LEVEL_LEADER,
            'position' => 'Leader',
            'division' => 'Software',
        ]);
        $sameDivisionStaff = $this->makeStaffUser([
            'name' => 'Software Staff',
            'email' => 'software.staff@example.test',
            'division' => 'Software',
        ]);
        $otherDivisionStaff = $this->makeStaffUser([
            'name' => 'Marketing Staff',
            'email' => 'marketing.staff@example.test',
            'division' => 'Marketing',
        ]);
        $sameLevelLeader = $this->makeStaffUser([
            'name' => 'Peer Leader',
            'email' => 'peer.leader@example.test',
            'level' => User::LEVEL_LEADER,
            'position' => 'Leader',
            'division' => 'Software',
        ]);

        $visibleReport = $this->createReportFor($sameDivisionStaff, [
            'completed_work' => 'Laporan staff satu divisi',
        ]);
        $this->createReportFor($otherDivisionStaff, [
            'completed_work' => 'Laporan staff beda divisi',
        ]);
        $this->createReportFor($sameLevelLeader, [
            'completed_work' => 'Laporan peer leader',
        ]);

        $response = $this->getJson('/api/mobile/team-daily-reports', [
            'Authorization' => 'Bearer '.$this->loginAndReturnToken($leader),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleReport->id)
            ->assertJsonPath('data.0.user.name', 'Software Staff')
            ->assertJsonPath('data.0.user.division', 'Software');
    }

    public function test_mobile_team_reports_are_filtered_by_date_and_default_to_today(): void
    {
        Carbon::setTestNow('2026-06-04 10:00:00');

        $leader = $this->makeStaffUser([
            'name' => 'Software Leader',
            'email' => 'leader.today@example.test',
            'level' => User::LEVEL_LEADER,
            'position' => 'Leader',
            'division' => 'Software',
        ]);
        $staff = $this->makeStaffUser([
            'name' => 'Software Staff',
            'email' => 'software.today@example.test',
            'division' => 'Software',
        ]);

        $todayReport = $this->createReportFor($staff, [
            'report_date' => '2026-06-04',
            'completed_work' => 'Laporan hari ini',
        ]);
        $this->createReportFor($staff, [
            'report_date' => '2026-06-03',
            'completed_work' => 'Laporan kemarin',
        ]);

        $token = $this->loginAndReturnToken($leader);

        $this->getJson('/api/mobile/team-daily-reports', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $todayReport->id)
            ->assertJsonPath('data.0.report_date', '2026-06-04');

        $this->getJson('/api/mobile/team-daily-reports?date=2026-06-03', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.report_date', '2026-06-03')
            ->assertJsonPath('data.0.completed_work', 'Laporan kemarin');

        Carbon::setTestNow();
    }

    public function test_mobile_staff_gets_empty_team_reports(): void
    {
        $staff = $this->makeStaffUser();
        $otherStaff = $this->makeStaffUser([
            'email' => 'other.staff@example.test',
            'division' => 'Software',
        ]);
        $this->createReportFor($otherStaff);

        $response = $this->getJson('/api/mobile/team-daily-reports', [
            'Authorization' => 'Bearer '.$this->loginAndReturnToken($staff),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_mobile_team_access_depends_on_lower_level_users_in_same_division(): void
    {
        $leader = $this->makeStaffUser([
            'name' => 'Software Leader',
            'email' => 'leader.access@example.test',
            'level' => User::LEVEL_LEADER,
            'position' => 'Leader',
            'division' => 'Software',
        ]);
        $staff = $this->makeStaffUser([
            'name' => 'Software Staff',
            'email' => 'staff.access@example.test',
            'division' => 'Software',
        ]);
        $leaderWithoutTeam = $this->makeStaffUser([
            'name' => 'Marketing Leader',
            'email' => 'leader.empty@example.test',
            'level' => User::LEVEL_LEADER,
            'position' => 'Leader',
            'division' => 'Marketing',
        ]);

        $this->getJson('/api/mobile/team-daily-reports/access', [
            'Authorization' => 'Bearer '.$this->loginAndReturnToken($leader),
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_view_team', true);

        $this->getJson('/api/mobile/team-daily-reports/access', [
            'Authorization' => 'Bearer '.$this->loginAndReturnToken($staff),
        ])->assertOk()
            ->assertJsonPath('data.can_view_team', false);

        $this->getJson('/api/mobile/team-daily-reports/access', [
            'Authorization' => 'Bearer '.$this->loginAndReturnToken($leaderWithoutTeam),
        ])->assertOk()
            ->assertJsonPath('data.can_view_team', false);
    }

    public function test_mobile_team_reports_follow_managed_divisions_when_configured(): void
    {
        $manager = $this->makeStaffUser([
            'name' => 'RnD Manager',
            'email' => 'manager.managed@example.test',
            'level' => User::LEVEL_MANAGER,
            'position' => 'Manager',
            'division' => 'RnD',
            'managed_divisions' => ['Software'],
        ]);
        $softwareStaff = $this->makeStaffUser([
            'name' => 'Managed Software Staff',
            'email' => 'managed.software@example.test',
            'division' => 'Software',
        ]);
        $rndStaff = $this->makeStaffUser([
            'name' => 'RnD Staff',
            'email' => 'rnd.staff@example.test',
            'division' => 'RnD',
        ]);

        $visibleReport = $this->createReportFor($softwareStaff);
        $this->createReportFor($rndStaff, [
            'completed_work' => 'Laporan divisi asal manager',
        ]);

        $token = $this->loginAndReturnToken($manager);

        $this->getJson('/api/mobile/team-daily-reports/access', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.can_view_team', true);

        $this->getJson('/api/mobile/team-daily-reports?date=2026-06-04', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleReport->id)
            ->assertJsonPath('data.0.user.division', 'Software');
    }

    private function makeStaffUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'company_id' => 1,
            'name' => 'Mobile Staff',
            'email' => 'mobile.staff@example.test',
            'password' => Hash::make('password'),
            'level' => User::LEVEL_STAFF,
            'division' => 'Software',
            'position' => 'Staff',
            'is_active' => true,
            'work_schedule' => User::SCHEDULE_5DAYS,
        ], $attributes));
    }

    private function loginAndReturnToken(User $user): string
    {
        return $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');
    }

    private function createReportFor(User $user, array $attributes = []): DailyReport
    {
        return DailyReport::create(array_merge($this->validReportPayload(), [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ], $attributes));
    }

    private function validReportPayload(): array
    {
        return [
            'report_date' => '2026-06-04',
            'overtime_status' => false,
            'overtime_start' => null,
            'overtime_end' => null,
            'completed_work' => 'Mengerjakan integrasi mobile daily',
            'unfinished_work' => 'Testing UI mobile',
            'obstacles' => 'Belum ada',
            'need_leader_help' => false,
            'leader_help_description' => null,
            'tomorrow_plan' => 'Lanjut integrasi riwayat laporan',
            'work_finished_at' => '17:00',
            'additional_notes' => null,
        ];
    }
}
