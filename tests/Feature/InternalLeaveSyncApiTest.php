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

    public function test_fully_covered_manual_record_is_absorbed_so_the_page_is_not_doubled(): void
    {
        $user = $this->makeUser('staff@example.test');

        $manual = $this->makeManualLeave($user, '2026-08-12', '2026-08-12', Leave::TYPE_SAKIT);

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_SAKIT,
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-13',
        ])->assertCreated()
            ->assertJsonPath('absorbed_manual_ids', [$manual->id])
            ->assertJsonPath('overlapping_manual_ids', []);

        $this->assertDatabaseMissing('leaves', ['id' => $manual->id]);

        // Yang tersisa hanya satu baris untuk rentang itu — tidak dobel.
        $this->assertSame(1, Leave::withoutGlobalScopes()->count());
    }

    public function test_manual_record_on_the_exact_same_day_is_absorbed(): void
    {
        // Kasus yang terlihat di halaman Cuti: HRIS dan manual persis satu tanggal sama.
        $user = $this->makeUser('staff@example.test');

        $manual = $this->makeManualLeave($user, '2026-07-13', '2026-07-13');

        $this->syncRequest([
            'external_id' => 'leave-req-77',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-13',
            'reason' => 'acara keluarga',
        ])->assertCreated()
            ->assertJsonPath('absorbed_manual_ids', [$manual->id]);

        $this->assertSame(1, Leave::withoutGlobalScopes()->count());
        $this->assertSame(Leave::SOURCE_ABSENSI, Leave::withoutGlobalScopes()->firstOrFail()->source);
    }

    public function test_manual_record_sticking_out_of_the_hris_range_is_kept(): void
    {
        $user = $this->makeUser('staff@example.test');

        // Manual 11-15 Agu, HRIS hanya menyetujui 12-14. Tanggal 11 dan 15 tidak
        // dijamin baris HRIS, jadi barisnya tidak boleh dihapus.
        $manual = $this->makeManualLeave($user, '2026-08-11', '2026-08-15');

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-14',
        ])->assertCreated()
            ->assertJsonPath('absorbed_manual_ids', [])
            ->assertJsonPath('overlapping_manual_ids', [$manual->id]);

        $this->assertDatabaseHas('leaves', ['id' => $manual->id]);
    }

    public function test_absorbed_manual_reason_is_carried_over_when_hris_sends_none(): void
    {
        $user = $this->makeUser('staff@example.test');

        $this->makeManualLeave($user, '2026-08-12', '2026-08-12', Leave::TYPE_CUTI, 'nikahan sepupu');

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])->assertCreated();

        $this->assertSame('nikahan sepupu', Leave::withoutGlobalScopes()->firstOrFail()->reason);
    }

    public function test_hris_reason_wins_over_the_absorbed_manual_reason(): void
    {
        $user = $this->makeUser('staff@example.test');

        $this->makeManualLeave($user, '2026-08-12', '2026-08-12', Leave::TYPE_CUTI, 'catatan lama');

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'reason' => 'acara keluarga',
        ])->assertCreated();

        $this->assertSame('acara keluarga', Leave::withoutGlobalScopes()->firstOrFail()->reason);
    }

    public function test_manual_record_of_another_user_is_never_touched(): void
    {
        $user = $this->makeUser('staff@example.test');
        $other = $this->makeUser('lain@example.test');

        $otherManual = $this->makeManualLeave($other, '2026-08-12', '2026-08-12');

        $this->syncRequest([
            'external_id' => 'leave-req-91',
            'email' => 'staff@example.test',
            'type' => Leave::TYPE_CUTI,
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
        ])->assertCreated()
            ->assertJsonPath('absorbed_manual_ids', []);

        $this->assertDatabaseHas('leaves', ['id' => $otherManual->id]);
        $this->assertSame($other->id, $otherManual->fresh()->user_id);
        $this->assertSame($user->id, Leave::withoutGlobalScopes()->where('external_id', 'leave-req-91')->value('user_id'));
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

    private function makeManualLeave(
        User $user,
        string $start,
        string $end,
        string $type = Leave::TYPE_CUTI,
        ?string $reason = null
    ): Leave {
        return Leave::withoutGlobalScopes()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $reason,
            'source' => Leave::SOURCE_MANUAL,
        ]);
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
