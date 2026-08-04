<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalMobileTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_endpoint_issues_mobile_token_by_email(): void
    {
        config(['services.absensi_bridge.secret' => 'bridge-secret']);

        $user = User::create([
            'company_id' => 1,
            'name' => 'Mobile Staff',
            'email' => 'staff@example.test',
            'password' => Hash::make('password'),
            'level' => User::LEVEL_STAFF,
            'division' => 'Software',
            'position' => 'Staff',
            'is_active' => true,
            'work_schedule' => User::SCHEDULE_5DAYS,
        ]);

        $response = $this
            ->withHeader('X-Internal-Secret', 'bridge-secret')
            ->postJson('/api/internal/mobile-token', [
                'email' => 'staff@example.test',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'division', 'position', 'level'],
            ]);

        $this->assertNotNull($user->fresh()->api_token_hash);
    }

    public function test_internal_endpoint_rejects_invalid_secret(): void
    {
        config(['services.absensi_bridge.secret' => 'bridge-secret']);

        $response = $this
            ->withHeader('X-Internal-Secret', 'wrong-secret')
            ->postJson('/api/internal/mobile-token', [
                'email' => 'staff@example.test',
            ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_internal_payroll_endpoint_returns_late_daily_report_counts_by_email(): void
    {
        config(['services.absensi_bridge.secret' => 'bridge-secret']);

        $staff = $this->makeUser('staff@example.test');
        $other = $this->makeUser('other@example.test');
        $this->createReport($staff, '2026-06-04', true);
        $this->createReport($staff, '2026-06-05', true);
        $this->createReport($staff, '2026-07-01', true);
        $this->createReport($other, '2026-06-04', false);

        $response = $this
            ->withHeader('X-Internal-Secret', 'bridge-secret')
            ->getJson('/api/internal/payroll/daily-report-late?'.http_build_query([
                'start' => '2026-06-01',
                'end' => '2026-06-30',
                'emails' => ['staff@example.test', 'other@example.test', 'missing@example.test'],
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.email', 'staff@example.test')
            ->assertJsonPath('data.0.late_days', 2)
            ->assertJsonPath('data.0.late_dates', ['2026-06-04', '2026-06-05'])
            ->assertJsonPath('data.1.email', 'other@example.test')
            ->assertJsonPath('data.1.late_days', 0)
            ->assertJsonPath('data.1.late_dates', [])
            ->assertJsonPath('data.2.email', 'missing@example.test')
            ->assertJsonPath('data.2.late_days', 0)
            ->assertJsonPath('data.2.late_dates', []);
    }

    private function makeUser(string $email): User
    {
        return User::create([
            'company_id' => 1,
            'name' => 'Mobile Staff',
            'email' => $email,
            'password' => Hash::make('password'),
            'level' => User::LEVEL_STAFF,
            'division' => 'Software',
            'position' => 'Staff',
            'is_active' => true,
            'work_schedule' => User::SCHEDULE_5DAYS,
        ]);
    }

    private function createReport(User $user, string $date, bool $isLate): DailyReport
    {
        return DailyReport::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'report_date' => $date,
            'overtime_status' => false,
            'completed_work' => 'Pekerjaan',
            'need_leader_help' => false,
            'tomorrow_plan' => 'Rencana',
            'work_finished_at' => '17:00',
            'is_late' => $isLate,
        ]);
    }
}
