<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\JciPasswordRecoveryOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_recover_a_password_with_a_code_and_the_old_session_is_revoked(): void
    {
        Notification::fake();
        $workspace = '/workspaces/123e4567-e89b-12d3-a456-426614174000';
        $cookies = [];
        $user = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
            'active_login_token' => 'previous-session',
            'active_login_seen_at' => now(),
        ]);

        $this->browse('GET', $workspace.'/login', $cookies)->assertOk()->assertSee('Forgot password?');
        $this->browse('GET', $workspace.'/forgot-password', $cookies)->assertOk()->assertSee('Send recovery code');
        $this->browse('POST', $workspace.'/forgot-password', $cookies, ['email' => 'unknown@example.test'])
            ->assertRedirect($workspace.'/forgot-password/verify');
        Notification::assertNothingSent();

        $this->browse('GET', $workspace.'/forgot-password', $cookies)->assertOk();
        $this->browse('POST', $workspace.'/forgot-password', $cookies, ['email' => $user->email])
            ->assertRedirect($workspace.'/forgot-password/verify');
        Notification::assertSentTo($user, JciPasswordRecoveryOtp::class);
        $notification = Notification::sent($user, JciPasswordRecoveryOtp::class)->first();
        $code = $notification->toMail($user)->introLines[1];

        $this->browse('GET', $workspace.'/forgot-password/verify', $cookies)->assertOk();
        $this->browse('POST', $workspace.'/forgot-password/verify', $cookies, ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->browse('POST', $workspace.'/forgot-password/verify', $cookies, ['code' => $code])
            ->assertRedirect($workspace.'/forgot-password/reset');
        $this->browse('GET', $workspace.'/forgot-password/reset', $cookies)->assertOk()->assertSee('Choose a new password.');
        $this->browse('POST', $workspace.'/forgot-password/reset', $cookies, [
            'password' => 'NewChapterPass123',
            'password_confirmation' => 'NewChapterPass123',
        ])->assertRedirect($workspace.'/login');

        $user->refresh();
        $this->assertTrue(Hash::check('NewChapterPass123', $user->password));
        $this->assertNull($user->active_login_token);
        $this->assertNull($user->active_login_seen_at);
        $this->browse('POST', $workspace.'/forgot-password/verify', $cookies, ['code' => $code])
            ->assertRedirect($workspace.'/forgot-password');
        $this->browse('POST', $workspace.'/login', $cookies, [
            'login' => $user->email,
            'password' => 'NewChapterPass123',
        ])->assertRedirect($workspace.'/dashboard');
    }

    private function browse(string $method, string $uri, array &$cookies, array $data = [])
    {
        Auth::forgetGuards();
        $response = $this->call($method, $uri, $data, $cookies);
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }
        return $response;
    }
}
