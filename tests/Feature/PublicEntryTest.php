<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\JciPasswordRecoveryOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, Hash, Notification};
use Tests\TestCase;

class PublicEntryTest extends TestCase
{
    use RefreshDatabase;

    private const WORKSPACE = '/workspaces/123e4567-e89b-12d3-a456-426614174000';

    public function test_public_and_workspace_pages_keep_navigation_and_assets_working(): void
    {
        foreach (['', self::WORKSPACE] as $prefix) {
            $cookies = [];
            $page = $this->browse('GET', $prefix.'/', $cookies)->assertOk()
                ->assertSee('Community stories')->assertSee('JCISTEM')
                ->assertSee('Project BLOOM')->assertSee('Project M.U.S.A.')
                ->assertSee('Habi at Kulay')->assertSee('Project PEACE+')
                ->assertSee('How to Spot the Red Heart')
                ->assertSee('href="http://localhost'.$prefix.'/login"', false)
                ->assertSee('href="http://localhost'.$prefix.'/contact"', false)
                ->assertSee(config('chapter.facebook_url'));

            preg_match_all('/(?:src|href)="(http:\/\/localhost\/assets\/[^"?]+)(?:\?[^\"]*)?"/', $page->getContent(), $assets);
            $this->assertNotEmpty($assets[1]);
            foreach ($assets[1] as $asset) {
                $this->assertFileExists(public_path(ltrim(parse_url($asset, PHP_URL_PATH), '/')));
            }

            if ($prefix) {
                $login = $this->browse('GET', $prefix.'/login', $cookies)->assertOk()
                    ->assertSee('action="http://localhost'.$prefix.'/login"', false)
                    ->assertSee('href="http://localhost'.$prefix.'/forgot-password"', false);
                preg_match('/class="[^"]*\bauth-home\b[^"]*" href="([^"]+)"/', $login->getContent(), $homeLink);
                $this->assertSame('http://localhost'.$prefix, rtrim($homeLink[1], '/'));
            }
        }
    }

    public function test_member_can_sign_in_with_username_and_email(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'member']);
        foreach ([$user->username, $user->email] as $identifier) {
            $cookies = [];
            $this->browse('GET', self::WORKSPACE.'/login', $cookies)->assertOk();
            $this->browse('POST', self::WORKSPACE.'/login', $cookies, [
                'login' => $identifier, 'password' => 'password',
            ])->assertRedirect(self::WORKSPACE.'/dashboard');
            $this->browse('GET', self::WORKSPACE.'/dashboard', $cookies)->assertOk();
            $this->browse('POST', self::WORKSPACE.'/logout', $cookies)->assertRedirect(self::WORKSPACE.'/login');
        }
    }

    public function test_invalid_sign_in_shows_an_accessible_field_error_without_refilling_password(): void
    {
        $cookies = [];
        $this->browse('GET', self::WORKSPACE.'/login', $cookies);
        $this->browse('POST', self::WORKSPACE.'/login', $cookies, [
            'login' => 'unknown@example.test', 'password' => 'DoNotReflectThisPassword',
        ])->assertSessionHasErrors('login');
        $this->browse('GET', self::WORKSPACE.'/login', $cookies)->assertOk()
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="auth-login-error"', false)
            ->assertSee('unknown@example.test')
            ->assertDontSee('DoNotReflectThisPassword');
    }

    public function test_login_never_exposes_or_replaces_an_existing_session(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'member',
            'active_login_token' => 'existing-session', 'active_login_seen_at' => now()]);
        $cookies = [];
        $this->browse('GET', self::WORKSPACE.'/login', $cookies);
        $credentials = ['login' => $user->email, 'password' => 'password'];
        $this->browse('POST', self::WORKSPACE.'/login', $cookies, $credentials)->assertSessionHasErrors('login');
        $page = $this->browse('GET', self::WORKSPACE.'/login', $cookies)->assertOk()
            ->assertSee('Use Forgot password? to restore secure account access.')
            ->assertDontSee('Already signed in')
            ->assertDontSee('another device')
            ->assertDontSee('replace_session', false);
        $this->assertStringNotContainsString('auth-session-help', $page->getContent());
        $this->assertSame('existing-session', $user->fresh()->active_login_token);
        $this->browse('POST', self::WORKSPACE.'/login', $cookies, $credentials + ['replace_session' => '1'])
            ->assertSessionHasErrors('login');
        $this->assertSame('existing-session', $user->fresh()->active_login_token);
    }

    public function test_all_three_recovery_steps_work_and_the_new_password_can_sign_in(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active', 'role' => 'member']);
        $cookies = [];
        $this->browse('GET', self::WORKSPACE.'/forgot-password', $cookies)->assertOk()->assertSee('Step 1 of 3');
        $this->browse('POST', self::WORKSPACE.'/forgot-password', $cookies, ['email' => $user->email])
            ->assertRedirect(self::WORKSPACE.'/forgot-password/verify');
        Notification::assertSentTo($user, JciPasswordRecoveryOtp::class);
        $notification = Notification::sent($user, JciPasswordRecoveryOtp::class)->first();
        $code = $notification->toMail($user)->introLines[1];
        $this->browse('GET', self::WORKSPACE.'/forgot-password/verify', $cookies)->assertOk()->assertSee('Step 2 of 3');
        $this->browse('POST', self::WORKSPACE.'/forgot-password/verify', $cookies, ['code' => $code])
            ->assertRedirect(self::WORKSPACE.'/forgot-password/reset');
        $this->browse('GET', self::WORKSPACE.'/forgot-password/reset', $cookies)->assertOk()->assertSee('Step 3 of 3');
        $this->browse('POST', self::WORKSPACE.'/forgot-password/reset', $cookies, [
            'password' => 'NewChapterPass123', 'password_confirmation' => 'NewChapterPass123',
        ])->assertRedirect(self::WORKSPACE.'/login');
        $this->assertTrue(Hash::check('NewChapterPass123', $user->fresh()->password));
        $this->browse('GET', self::WORKSPACE.'/login', $cookies)->assertOk()->assertSee('Your password has been updated.');
        $this->browse('POST', self::WORKSPACE.'/login', $cookies, [
            'login' => $user->email, 'password' => 'NewChapterPass123',
        ])->assertRedirect(self::WORKSPACE.'/dashboard');
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
