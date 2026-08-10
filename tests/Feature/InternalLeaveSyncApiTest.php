<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalLeaveSyncApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.absensi_bridge.secret' => 'bridge-secret']);
    }

    public function test_approved_leave_from_hris_is_recorded_in_daily(): void
    {
        $user = $this->makeUser('staff@example.test');

        $response = $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_SAKIT,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-13',
            'reason' => 'Surat dokter',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('created', true)
            ->assertJsonPath('data.days_count', 3);

        $this->assertDatabaseHas('leaves', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'type' => Leave::TYPE_SAKIT,
            'source' => Leave::SOURCE_ABSENSI,
            'external_id' => 'leave-req-91',
            'reason' => 'Surat dokter',
        ]);
    }

    public function test_repeated_sync_updates_the_same_row_instead_of_duplicating(): void
    {
        $this->makeUser('staff@example.test');

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
        ])->assertCreated();

        // Tanggal direvisi lalu di-ACC ulang di HRIS.
        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-14',
        ])->assertOk()
            ->assertJsonPath('created', false);

        $this->assertSame(1, Leave::withoutGlobalScopes()->count());

        $leave = Leave::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('leave-req-91', $leave->external_id);
        $this->assertSame('2026-08-12', $leave->start_date->toDateString());
        $this->assertSame('2026-08-14', $leave->end_date->toDateString());
    }

    public function test_revoking_an_approval_removes_the_row(): void
    {
        $this->makeUser('staff@example.test');

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
        ])->assertCreated();

        $this->withHeader('X-Internal-Secret', 'bridge-secret')
            ->deleteJson('/api/internal/leaves/leave-req-91')
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertSame(0, Leave::withoutGlobalScopes()->count());

        // Pembatalan yang terkirim dua kali tetap sukses, bukan error.
        $this->withHeader('X-Internal-Secret', 'bridge-secret')
            ->deleteJson('/api/internal/leaves/leave-req-91')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted', false);
    }

    public function test_sync_reports_overlapping_manual_records(): void
    {
        $user = $this->makeUser('staff@example.test');

        $manual = Leave::withoutGlobalScopes()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => Leave::TYPE_SAKIT,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'source' => Leave::SOURCE_MANUAL,
        ]);

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_SAKIT,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-13',
        ])->assertCreated()
            ->assertJsonPath('overlapping_manual_ids', [$manual->id]);

        // Catatan manual karyawan tidak dihapus oleh sinkron.
        $this->assertDatabaseHas('leaves', ['id' => $manual->id]);
    }

    public function test_unknown_email_is_rejected_without_creating_anything(): void
    {
        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'bukan-karyawan-daily@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
        ])->assertNotFound()
            ->assertJsonPath('success', false);

        $this->assertSame(0, Leave::withoutGlobalScopes()->count());
    }

    public function test_inactive_user_is_rejected(): void
    {
        $user = $this->makeUser('resign@example.test');
        $user->forceFill(['is_active' => false])->save();

        $this->syncRequest([
            'external_id' => 'leave-req-92',
            'email' => 'resign@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
        ])->assertNotFound();
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->makeUser('staff@example.test');

        $this->syncRequest([
            'external_id' => 'leave-req-93',
            'email' => 'staff@example.test',
            'type' => 'wfh',
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
        ])->assertStatus(422);
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $this->makeUser('staff@example.test');

        $this->syncRequest([
            'external_id' => 'leave-req-94',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-11',
        ])->assertStatus(422);
    }

    public function test_invalid_secret_is_rejected_on_both_endpoints(): void
    {
        $this->withHeader('X-Internal-Secret', 'wrong-secret')
            ->postJson('/api/internal/leaves/sync', [
                'external_id' => 'leave-req-91',
                'email' => 'staff@example.test',
                'type' => Leave::TYPE_CUTI,
                'start_date' => '2026-08-11',
                'end_date' => '2026-08-11',
            ])
            ->assertForbidden();

        $this->withHeader('X-Internal-Secret', 'wrong-secret')
            ->deleteJson('/api/internal/leaves/leave-req-91')
            ->assertForbidden();
    }

    public function test_synced_row_cannot_be_deleted_by_the_employee(): void
    {
        $user = $this->makeUser('staff@example.test');

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
        ])->assertCreated();

        $leave = Leave::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('leaves.destroy', $leave))
            ->assertRedirect(route('leaves.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('leaves', ['id' => $leave->id]);
    }

    public function test_manual_row_is_still_deletable_by_the_employee(): void
    {
        $user = $this->makeUser('staff@example.test');

        $leave = Leave::withoutGlobalScopes()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
            'source' => Leave::SOURCE_MANUAL,
        ]);

        $this->actingAs($user)
            ->delete(route('leaves.destroy', $leave))
            ->assertRedirect(route('leaves.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('leaves', ['id' => $leave->id]);
    }

    public function test_employee_can_still_record_more_than_one_manual_leave(): void
    {
        // Unique index (source, external_id) tidak boleh memblokir baris manual,
        // yang external_id-nya selalu null.
        $user = $this->makeUser('staff@example.test');

        $this->actingAs($user)->post(route('leaves.store'), [
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
        ])->assertRedirect(route('leaves.index'));

        $this->actingAs($user)->post(route('leaves.store'), [
            'type' => Leave::TYPE_SAKIT,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
        ])->assertRedirect(route('leaves.index'));

        $this->assertSame(2, Leave::withoutGlobalScopes()->count());
        $this->assertSame(
            2,
            Leave::withoutGlobalScopes()->where('source', Leave::SOURCE_MANUAL)->count()
        );
    }

    private function syncRequest(array $payload)
    {
        return $this->withHeader('X-Internal-Secret', 'bridge-secret')
            ->postJson('/api/internal/leaves/sync', $payload);
    }

    private function makeUser(string $email): User
    {
        return User::create([
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
    }
}
