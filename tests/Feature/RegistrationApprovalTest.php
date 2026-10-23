<?php

namespace Tests\Feature;

use App\Models\{RegistrationRequest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function application(string $email = 'applicant@example.test'): array
    {
        return [
            'name' => 'Carmona Applicant',
            'username' => 'carmona.applicant',
            'email' => $email,
            'phone' => '09171234567',
            'password' => 'StrongMemberPass123',
            'password_confirmation' => 'StrongMemberPass123',
            'consent' => '1',
        ];
    }

    public function test_only_admin_approval_creates_an_active_member_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $bod = User::factory()->create(['role' => 'bod', 'status' => 'active']);

        $this->get('/register')->assertOk()->assertSee('Register an account.');
        $workspace = '/workspaces/123e4567-e89b-12d3-a456-426614174000';
        $this->get($workspace.'/register')->assertOk()->assertSee('action="http://localhost'.$workspace.'/register"', false);
        $this->post('/register', $this->application())->assertRedirect('/register/thanks');
        $registration = RegistrationRequest::firstOrFail();
        $this->assertSame('pending', $registration->status);
        $this->assertTrue(Hash::check('StrongMemberPass123', $registration->password));
        $this->assertDatabaseMissing('users', ['email' => 'applicant@example.test']);
        $this->post('/login', ['login' => 'carmona.applicant', 'password' => 'StrongMemberPass123'])
            ->assertSessionHasErrors('login');

        $this->actingAs($bod)->get('/registrations')->assertForbidden();
        $this->actingAs($bod)->post('/registrations/'.$registration->id.'/review', ['decision' => 'approve'])->assertForbidden();
        $this->actingAs($admin)->get('/registrations')->assertOk()->assertSee('Carmona Applicant');
        $this->actingAs($admin)->post('/registrations/'.$registration->id.'/review', ['decision' => 'approve'])
            ->assertSessionHasErrors('verified_identity');
        $this->actingAs($admin)->post('/registrations/'.$registration->id.'/review', ['decision' => 'approve', 'verified_identity' => '1'])
            ->assertRedirect('/registrations/'.$registration->id);

        $registration->refresh();
        $member = User::where('email', 'applicant@example.test')->firstOrFail();
        $this->assertSame('approved', $registration->status);
        $this->assertSame($admin->id, $registration->reviewed_by);
        $this->assertSame($member->id, $registration->user_id);
        $this->assertNull($registration->password);
        $this->assertSame('member', $member->role);
        $this->assertSame('active', $member->status);
        $this->assertTrue(Hash::check('StrongMemberPass123', $member->password));
        auth()->logout();
        $this->actingAs($admin)->post('/registrations/'.$registration->id.'/review', ['decision' => 'approve', 'verified_identity' => '1'])
            ->assertSessionHasErrors('decision');
        $this->assertSame(1, User::where('email', 'applicant@example.test')->count());
        auth()->logout();
        $this->post('/login', ['login' => 'carmona.applicant', 'password' => 'StrongMemberPass123'])->assertRedirect();
        $this->assertAuthenticatedAs($member);
    }

    public function test_rejection_never_creates_an_account_and_discards_the_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->post('/register', $this->application())->assertRedirect();
        $registration = RegistrationRequest::firstOrFail();

        $this->actingAs($admin)->post('/registrations/'.$registration->id.'/review', ['decision' => 'reject'])
            ->assertSessionHasErrors('review_notes');
        $this->actingAs($admin)->post('/registrations/'.$registration->id.'/review', [
            'decision' => 'reject', 'review_notes' => 'Unable to verify chapter membership.',
        ])->assertRedirect('/registrations/'.$registration->id);

        $this->assertSame('rejected', $registration->fresh()->status);
        $this->assertNull($registration->fresh()->password);
        $this->assertDatabaseMissing('users', ['email' => 'applicant@example.test']);
        auth()->logout();
    }

    public function test_duplicate_accounts_and_requests_are_not_created(): void
    {
        $this->post('/register', $this->application())->assertRedirect();
        $this->post('/register', $this->application())->assertSessionHasErrors('email');
        $this->assertSame(1, RegistrationRequest::count());
    }
}
