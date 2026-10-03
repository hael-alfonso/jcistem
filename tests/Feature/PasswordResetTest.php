<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\JciPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_request_and_use_a_password_reset_link_in_a_workspace(): void
    {
        Notification::fake();
        $workspace = '/workspaces/123e4567-e89b-12d3-a456-426614174000';
        $user = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
            'active_login_token' => 'previous-session',
            'active_login_seen_at' => now(),
        ]);

        $this->get($workspace.'/login')->assertOk()->assertSee('Forgot password?');
        $this->get($workspace.'/forgot-password')->assertOk()->assertSee('Request reset link');
        $this->post($workspace.'/forgot-password', ['email' => 'unknown@example.test'])->assertSessionHas('status');
        Notification::assertNothingSent();

        $this->post($workspace.'/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, JciPasswordReset::class);
        $notification = Notification::sent($user, JciPasswordReset::class)->first();
        $token = $notification->token;

        $this->get($workspace.'/reset-password/'.$token.'?email='.urlencode($user->email))
            ->assertOk()->assertSee('Create a new password');
        $this->post($workspace.'/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewChapterPass123',
            'password_confirmation' => 'NewChapterPass123',
        ])->assertSessionHasErrors('email');

        $this->post($workspace.'/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewChapterPass123',
            'password_confirmation' => 'NewChapterPass123',
        ])->assertRedirect($workspace.'/login');

        $user->refresh();
        $this->assertTrue(Hash::check('NewChapterPass123', $user->password));
        $this->assertNull($user->active_login_token);
        $this->assertNull($user->active_login_seen_at);
        $this->post($workspace.'/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'AnotherChapterPass123',
            'password_confirmation' => 'AnotherChapterPass123',
        ])->assertSessionHasErrors('email');

        $this->post($workspace.'/login', [
            'email' => $user->email,
            'password' => 'NewChapterPass123',
        ])->assertRedirect($workspace.'/dashboard');
    }
}
