<?php

namespace Tests\Feature;

use App\Models\{Project, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveMemberRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_general_members_inactive_for_a_full_year_are_auto_removed(): void
    {
        $this->travelTo(now('Asia/Manila')->startOfDay());
        $old = User::factory()->create(['role' => 'member', 'status' => 'inactive']);
        $old->forceFill(['inactive_since' => now()->subYear()->subDay()])->save();
        $recent = User::factory()->create(['role' => 'member', 'status' => 'inactive']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'inactive']);
        $treasurer->forceFill(['inactive_since' => now()->subYears(2)])->save();
        $active = User::factory()->create(['role' => 'member', 'status' => 'active']);

        $this->artisan('members:remove-inactive')->assertSuccessful();

        $this->assertSoftDeleted($old);
        $this->assertNotNull($recent->fresh());
        $this->assertNotNull($treasurer->fresh());
        $this->assertNotNull($active->fresh());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_account_auto_removed', 'object_id' => $old->id, 'user_id' => null,
        ]);

        $this->artisan('members:remove-inactive')->assertSuccessful();
        $this->assertSame(1, User::onlyTrashed()->count());
        $this->travelBack();
    }

    public function test_reactivation_resets_the_clock_and_active_project_chairs_are_kept(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'inactive']);
        $member->forceFill(['inactive_since' => now()->subYears(2)])->save();
        $member->status = 'active';
        $member->save();
        $this->assertNull($member->fresh()->inactive_since);
        $member->status = 'inactive';
        $member->save();
        $this->assertTrue($member->fresh()->inactive_since->greaterThan(now()->subMinute()));

        $chair = User::factory()->create(['role' => 'member', 'status' => 'inactive']);
        $chair->forceFill(['inactive_since' => now()->subYears(2)])->save();
        Project::create([
            'created_by' => $chair->id, 'chair_id' => $chair->id,
            'title' => 'Open chapter project', 'status' => 'Ongoing',
        ]);

        $this->artisan('members:remove-inactive')->assertSuccessful();
        $this->assertNotNull($chair->fresh());
        $this->assertNotNull($member->fresh());
    }
}
