<?php

namespace Tests\Feature;

use App\Models\{AuditLog, Project, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSecurityAndMonthFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_active_role_can_change_only_its_own_password_with_current_password(): void
    {
        foreach (['admin', 'treasurer', 'bod', 'member'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active', 'password' => Hash::make('OriginalPass123')]);
            $this->actingAs($user)->get(route('account'))->assertOk()->assertSee('Change password');
            $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'workspace_page_opened', 'remarks' => 'Opened Account.']);
            $this->put(route('account.password'), [
                'current_password' => 'wrong', 'password' => 'NewSecretPass123', 'password_confirmation' => 'NewSecretPass123',
            ])->assertSessionHasErrors('current_password');
            $this->assertTrue(Hash::check('OriginalPass123', $user->fresh()->password));
            $this->put(route('account.password'), [
                'current_password' => 'OriginalPass123', 'password' => 'NewSecretPass123', 'password_confirmation' => 'NewSecretPass123',
            ])->assertSessionHasNoErrors();
            $this->assertTrue(Hash::check('NewSecretPass123', $user->fresh()->password));
            $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'account_password_changed']);
            $this->assertDatabaseMissing('audit_logs', ['user_id' => $user->id, 'action' => 'workspace_request_submitted', 'remarks' => 'Submitted Account Password.']);
            $this->assertStringNotContainsString('NewSecretPass123', AuditLog::where('user_id', $user->id)->where('action', 'account_password_changed')->first()->toJson());
        }
    }

    public function test_project_month_filter_includes_the_entire_end_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Project::create(['created_by' => $admin->id, 'title' => 'January project', 'area' => 'Community Impact', 'status' => 'Admin Review', 'created_at' => '2026-01-31 20:00:00']);
        Project::create(['created_by' => $admin->id, 'title' => 'February project', 'area' => 'Community Impact', 'status' => 'Admin Review', 'created_at' => '2026-02-01 09:00:00']);

        $this->actingAs($admin)->get(route('projects', ['from' => '2026-01', 'to' => '2026-01']))->assertOk()
            ->assertSee('January project')->assertDontSee('February project')
            ->assertSee('type="month"', false);
    }
}
