<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_bod_can_edit_members_without_changing_account_access(): void
    {
        $bod = User::factory()->create(['role' => 'bod', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'inactive', 'profile' => ['interests' => ['Community Impact']]]);
        $this->actingAs($bod)->get('/members/'.$member->id.'/edit')->assertOk()
            ->assertDontSee('name="password"', false)->assertDontSee('name="role"', false);
        $this->put('/members/'.$member->id, ['name' => 'Updated member', 'profile' => ['phone' => '09171234567']])
            ->assertRedirect('/members')->assertSessionHasNoErrors();
        $this->assertSame('Updated member', $member->fresh()->name);
        $this->assertSame('inactive', $member->fresh()->status);
        $this->assertSame(['Community Impact'], $member->fresh()->profile['interests']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_profile_saved', 'user_id' => $bod->id]);
        foreach (['role' => 'admin', 'status' => 'active', 'password' => 'AttackPass123', 'email' => 'attacker@example.test', 'concept_reviewer' => '1'] as $key => $value) {
            $this->put('/members/'.$member->id, ['name' => 'Tampered', $key => $value])->assertForbidden();
        }
        $this->assertSame('member', $member->fresh()->role);
        $this->assertSame('Updated member', $member->fresh()->name);
        $this->get('/members/create')->assertForbidden();
        $this->post('/members', ['name' => 'Bypass'])->assertForbidden();
    }

    public function test_ordinary_users_cannot_manage_members_and_inactive_users_cannot_enter(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        foreach (['member', 'treasurer'] as $role) {
            $actor = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($actor)->get('/members/'.$member->id.'/edit')->assertForbidden();
            $this->put('/members/'.$member->id, ['name' => 'Tampered'])->assertForbidden();
            $this->get('/registrations')->assertForbidden();
        }
        $this->assertSame($member->name, $member->fresh()->name);
        foreach (['pending', 'rejected', 'inactive'] as $status) {
            $actor = User::factory()->create(['role' => 'admin', 'status' => $status]);
            $response = $this->actingAs($actor)->get('/dashboard');
            $this->assertContains($response->status(), [302, 403]);
        }
    }

    public function test_public_member_directory_hides_inactive_accounts_while_managers_can_find_them(): void
    {
        $active = User::factory()->create(['name' => 'Active Volunteer', 'role' => 'member', 'status' => 'active']);
        $inactive = User::factory()->create(['name' => 'Former Volunteer', 'role' => 'member', 'status' => 'inactive']);
        $viewer = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $bod = User::factory()->create(['role' => 'bod', 'status' => 'active']);

        $this->actingAs($viewer)->get(route('members'))->assertOk()
            ->assertSee($active->name)->assertDontSee($inactive->name);
        $this->actingAs($bod)->get(route('members.manage', ['q' => 'Former']))->assertOk()
            ->assertSee($inactive->name)->assertSee(route('members.manage'), false);
    }
}
