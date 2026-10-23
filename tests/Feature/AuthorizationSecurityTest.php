<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'status' => 'active',
            'concept_reviewer' => false,
            'proposal_reviewer' => false,
        ], $attributes));
    }

    public function test_finance_and_administration_are_separated_by_role(): void
    {
        $admin = $this->user('admin');
        $treasurer = $this->user('treasurer');
        $bod = $this->user('bod');
        $member = $this->user('member');

        $this->actingAs($treasurer)->get('/ledger')->assertOk();
        $this->get('/dues/manage')->assertOk();
        $this->get('/audit')->assertForbidden();
        $this->get('/members/create')->assertForbidden();

        $this->actingAs($admin)->get('/audit')->assertOk();
        $this->get('/members/create')->assertOk();
        $this->get('/ledger')->assertForbidden();
        $this->get('/dues/manage')->assertForbidden();

        foreach ([$bod, $member] as $user) {
            $this->actingAs($user)->get('/ledger')->assertForbidden();
            $this->get('/audit')->assertForbidden();
            $this->get('/members/create')->assertForbidden();
        }
    }

    public function test_review_permissions_require_both_an_eligible_role_and_explicit_assignment(): void
    {
        $bod = $this->user('bod');
        $reviewer = $this->user('bod', ['concept_reviewer' => true, 'proposal_reviewer' => true]);
        $member = $this->user('member', ['concept_reviewer' => true, 'proposal_reviewer' => true]);
        $inactiveAdmin = $this->user('admin', ['status' => 'inactive', 'concept_reviewer' => true]);

        $this->assertFalse(Permissions::allows($bod, Permissions::CONCEPT_REVIEW));
        $this->assertTrue(Permissions::allows($reviewer, Permissions::CONCEPT_REVIEW));
        $this->assertTrue(Permissions::allows($reviewer, Permissions::PROPOSAL_REVIEW));
        $this->assertFalse(Permissions::allows($member, Permissions::CONCEPT_REVIEW));
        $this->assertFalse(Permissions::allows($inactiveAdmin, Permissions::CONCEPT_REVIEW));
    }

    public function test_sensitive_pages_are_not_cached_and_security_headers_are_present(): void
    {
        Auth::logout();
        Auth::forgetGuards();
        $this->get('/workspaces/123e4567-e89b-12d3-a456-426614174000/login')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($this->user('member'))->get('/dashboard')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_application_health_endpoint_remains_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
