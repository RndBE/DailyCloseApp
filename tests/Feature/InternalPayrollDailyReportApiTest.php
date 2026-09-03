<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalPayrollDailyReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.absensi_bridge.secret' => 'bridge-secret']);

        // Kamis 10 Sep 2026 — hari berjalan, dipakai untuk menguji batas "sampai kemarin".
        $this->travelTo(Carbon::parse('2026-09-10 12:00:00'));
    }

    public function test_hari_tanpa_laporan_ikut_dihitung_sebagai_sanksi(): void
    {
        $user = $this->makeUser('zaini@example.test');

        // 1 Sep: laporan telat. 2 Sep: laporan tepat waktu.
        $this->makeReport($user, '2026-09-01', isLate: true);
        $this->makeReport($user, '2026-09-02', isLate: false);

        // 3 Sep cuti, 4 Sep libur nasional — keduanya bukan sanksi.
        $this->makeLeave($user, '2026-09-03');
        Holiday::create(['date' => '2026-09-04', 'name' => 'Libur Uji', 'type' => Holiday::TYPE_NASIONAL]);

        // 5-6 Sep akhir pekan. 7, 8, 9 Sep bolong. 10 Sep hari berjalan.
        $response = $this->lateCounts('2026-09-01', '2026-09-10', ['zaini@example.test']);

        $response->assertOk()
            ->assertJsonPath('data.0.missing_days', 3)
            ->assertJsonPath('data.0.missing_dates', ['2026-09-07', '2026-09-08', '2026-09-09'])
            ->assertJsonPath('data.0.late_days', 4)
            ->assertJsonPath('data.0.late_dates', [
                '2026-09-01',
                '2026-09-07',
                '2026-09-08',
                '2026-09-09',
            ]);
    }

    public function test_hari_berjalan_tidak_dihitung_bolong(): void
    {
        $this->makeUser('zaini@example.test');

        // Rentang hanya berisi hari ini (Kamis 10 Sep) — belum jatuh tempo.
        $this->lateCounts('2026-09-10', '2026-09-10', ['zaini@example.test'])
            ->assertOk()
            ->assertJsonPath('data.0.missing_days', 0)
            ->assertJsonPath('data.0.late_days', 0);
    }

    public function test_laporan_rapel_menutup_hari_bolong(): void
    {
        $user = $this->makeUser('zaini@example.test');

        // Diisi belakangan untuk tanggal mundur; is_late tetap false (aturan lama).
        $this->makeReport($user, '2026-09-07', isLate: false);
        $this->makeReport($user, '2026-09-08', isLate: false);
        $this->makeReport($user, '2026-09-09', isLate: false);

        $this->lateCounts('2026-09-07', '2026-09-09', ['zaini@example.test'])
            ->assertOk()
            ->assertJsonPath('data.0.missing_days', 0)
            ->assertJsonPath('data.0.late_days', 0);
    }

    public function test_akhir_pekan_jadwal_6_hari_hanya_minggu(): void
    {
        $user = $this->makeUser('sabtu@example.test');
        $user->forceFill(['work_schedule' => User::SCHEDULE_6DAYS])->save();

        // Sabtu 5 Sep ikut dihitung, Minggu 6 Sep tidak.
        $this->lateCounts('2026-09-05', '2026-09-06', ['sabtu@example.test'])
            ->assertOk()
            ->assertJsonPath('data.0.missing_dates', ['2026-09-05']);
    }

    public function test_security_tidak_kena_sanksi_bolong(): void
    {
        $user = $this->makeUser('satpam@example.test');
        $user->forceFill(['work_schedule' => User::SCHEDULE_SECURITY])->save();

        $this->lateCounts('2026-09-01', '2026-09-09', ['satpam@example.test'])
            ->assertOk()
            ->assertJsonPath('data.0.missing_days', 0)
            ->assertJsonPath('data.0.late_days', 0);
    }

    public function test_manager_tidak_kena_sanksi_bolong(): void
    {
        $user = $this->makeUser('manager@example.test');
        $user->forceFill(['level' => User::LEVEL_MANAGER])->save();

        $this->lateCounts('2026-09-01', '2026-09-09', ['manager@example.test'])
            ->assertOk()
            ->assertJsonPath('data.0.missing_days', 0);
    }

    public function test_hari_sebelum_akun_dibuat_tidak_dihitung(): void
    {
        $user = $this->makeUser('baru@example.test');
        $user->forceFill(['created_at' => '2026-09-08 09:00:00'])->save();

        $this->lateCounts('2026-09-01', '2026-09-09', ['baru@example.test'])
            ->assertOk()
            ->assertJsonPath('data.0.missing_dates', ['2026-09-08', '2026-09-09']);
    }

    public function test_email_tak_dikenal_tetap_muncul_dengan_nilai_nol(): void
    {
        $this->lateCounts('2026-09-01', '2026-09-09', ['bukan-karyawan@example.test'])
            ->assertOk()
            ->assertJsonPath('data.0.email', 'bukan-karyawan@example.test')
            ->assertJsonPath('data.0.late_days', 0)
            ->assertJsonPath('data.0.missing_days', 0);
    }

    public function test_secret_salah_ditolak(): void
    {
        $this->withHeader('X-Internal-Secret', 'wrong-secret')
            ->getJson('/api/internal/payroll/daily-report-late?'.http_build_query([
                'start' => '2026-09-01',
                'end' => '2026-09-09',
                'emails' => ['zaini@example.test'],
            ]))
            ->assertForbidden();
    }

    private function lateCounts(string $start, string $end, array $emails)
    {
        return $this->withHeader('X-Internal-Secret', 'bridge-secret')
            ->getJson('/api/internal/payroll/daily-report-late?'.http_build_query([
                'start' => $start,
                'end' => $end,
                'emails' => $emails,
            ]));
    }

    private function makeReport(User $user, string $date, bool $isLate): DailyReport
    {
        return DailyReport::withoutGlobalScopes()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'report_date' => $date,
            'completed_work' => 'Kerjaan selesai',
            'tomorrow_plan' => 'Lanjut besok',
            'work_finished_at' => '17:00',
            'is_late' => $isLate,
        ]);
    }

    private function makeLeave(User $user, string $date): Leave
    {
        return Leave::withoutGlobalScopes()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => Leave::TYPE_CUTI,
            'start_date' => $date,
            'end_date' => $date,
            'source' => Leave::SOURCE_MANUAL,
        ]);
    }

    private function makeUser(string $email): User
    {
        $user = User::create([
            'company_id' => 1,
            'name' => 'Staff Daily',
            'email' => $email,
            'password' => Hash::make('password'),
            'level' => User::LEVEL_STAFF,
            'division' => 'Software',
            'position' => 'Staff',
            'is_active' => true,
            'work_schedule' => User::SCHEDULE_5DAYS,
        ]);

        $user->forceFill(['created_at' => '2026-01-01 08:00:00'])->save();

        return $user->fresh();
    }
}
