<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ConcurrentWorkspaceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_users_of_every_role_can_stay_signed_in_together(): void
    {
        $admin = User::factory()->create(['name' => 'Alicia Admin', 'role' => 'admin', 'status' => 'active']);
        $cookies = [];

        $adminWorkspace = $this->newWorkspace($cookies);
        $this->signIn($adminWorkspace, $admin, $cookies);
        $duplicateAdminWorkspace = $this->newWorkspace($cookies);
        $this->requestAsBrowser('POST', $duplicateAdminWorkspace.'/login', $cookies, [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect($duplicateAdminWorkspace.'/login')->assertSessionHasErrors('email');
        $this->visitDashboard($adminWorkspace, $cookies)->assertOk()->assertSee('Alicia Admin');
        $workspaces = ['admin' => $adminWorkspace];
        foreach ([
            'bod' => ['Brenda Board', 'brenda@example.test'],
            'treasurer' => ['Tina Treasurer', 'tina@example.test'],
            'member' => ['Marco Member', 'marco@example.test'],
        ] as $role => [$name, $email]) {
            $this->requestAsBrowser('POST', $adminWorkspace.'/members', $cookies, [
                'name' => $name,
                'email' => $email,
                'password' => 'MemberPass123',
                'password_confirmation' => 'MemberPass123',
                'role' => $role,
                'status' => 'active',
            ])->assertRedirect($adminWorkspace.'/members');

            $workspaces[$role] = $this->newWorkspace($cookies, $role === 'bod' ? $adminWorkspace.'/login' : '/login');
            $this->signIn($workspaces[$role], User::where('email', $email)->firstOrFail(), $cookies, 'MemberPass123');
        }
        $this->assertCount(4, array_unique($workspaces));

        $this->visitDashboard($adminWorkspace, $cookies)
            ->assertOk()->assertSee('Alicia')->assertSee('Admin workspace')
            ->assertSee('Open another account')->assertSee('href="/login" target="_blank"', false);
        foreach (['bod' => 'Brenda', 'treasurer' => 'Tina', 'member' => 'Marco'] as $role => $firstName) {
            $this->visitDashboard($workspaces[$role], $cookies)
                ->assertOk()->assertSee($firstName)->assertSee(\App\Support\WorkspaceNav::label($role).' workspace');
        }

        $memberWorkspace = $workspaces['member'];
        $this->requestAsBrowser('POST', $memberWorkspace.'/logout', $cookies)->assertRedirect($memberWorkspace.'/login');
        $this->visitDashboard($adminWorkspace, $cookies)->assertOk()->assertSee('Alicia');
        $this->visitDashboard($workspaces['bod'], $cookies)->assertOk()->assertSee('Brenda');
        $this->visitDashboard($workspaces['treasurer'], $cookies)->assertOk()->assertSee('Tina');
        $this->visitDashboard($memberWorkspace, $cookies)->assertRedirect($memberWorkspace.'/login');

        $this->requestAsBrowser('POST', $adminWorkspace.'/logout', $cookies)
            ->assertRedirect($adminWorkspace.'/login');
        $this->signIn($duplicateAdminWorkspace, $admin, $cookies);
        $this->visitDashboard($workspaces['bod'], $cookies)->assertOk()->assertSee('Brenda');
    }

    public function test_expired_login_claim_can_be_reused_and_the_old_session_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Alicia Admin', 'role' => 'admin', 'status' => 'active']);
        $cookies = [];
        $firstWorkspace = $this->newWorkspace($cookies);
        $this->signIn($firstWorkspace, $user, $cookies);
        $oldToken = $user->fresh()->active_login_token;

        User::whereKey($user->id)->update([
            'active_login_seen_at' => now()->subMinutes(config('session.lifetime') + 1),
        ]);
        $secondWorkspace = $this->newWorkspace($cookies);
        $this->signIn($secondWorkspace, $user, $cookies);

        $this->assertNotSame($oldToken, $user->fresh()->active_login_token);
        $this->visitDashboard($firstWorkspace, $cookies)->assertRedirect($firstWorkspace.'/login');
        $this->visitDashboard($secondWorkspace, $cookies)->assertOk()->assertSee('Alicia Admin');
    }

    public function test_existing_signed_in_session_is_adopted_and_blocks_a_second_login(): void
    {
        $user = User::factory()->create(['name' => 'Alicia Admin', 'role' => 'admin', 'status' => 'active']);
        $cookies = [];
        $workspace = $this->newWorkspace($cookies);
        $this->signIn($workspace, $user, $cookies);

        $session = app('session')->driver();
        $legacyToken = hash('sha256', $session->getId());
        $session->forget('active_login_token');
        $session->save();
        User::whereKey($user->id)->update([
            'active_login_token' => $legacyToken,
            'active_login_seen_at' => now(),
        ]);

        $this->visitDashboard($workspace, $cookies)->assertOk()->assertSee('Alicia Admin');
        $this->assertSame($legacyToken, $session->get('active_login_token'));

        $newWorkspace = $this->newWorkspace($cookies);
        $this->requestAsBrowser('POST', $newWorkspace.'/login', $cookies, [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($newWorkspace.'/login')->assertSessionHasErrors('email');
        $this->visitDashboard($workspace, $cookies)->assertOk()->assertSee('Alicia Admin');
    }

    public function test_remembered_account_can_reclaim_its_login_after_expiry(): void
    {
        $user = User::factory()->create(['name' => 'Alicia Admin', 'role' => 'admin', 'status' => 'active']);
        $cookies = [];
        $workspace = $this->newWorkspace($cookies);
        $this->requestAsBrowser('POST', $workspace.'/login', $cookies, [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect($workspace.'/dashboard');
        $oldToken = $user->fresh()->active_login_token;
        $this->assertTrue(collect(array_keys($cookies))->contains(fn ($name) => str_starts_with($name, 'remember_web_')));

        unset($cookies[config('session.cookie').'_'.basename($workspace)]);
        User::whereKey($user->id)->update([
            'active_login_seen_at' => now()->subMinutes(config('session.lifetime') + 1),
        ]);

        $this->visitDashboard($workspace, $cookies)->assertOk()->assertSee('Alicia Admin');
        $this->assertNotSame($oldToken, $user->fresh()->active_login_token);
    }

    private function newWorkspace(array &$cookies, string $entry = '/login'): string
    {
        $response = $this->requestAsBrowser('GET', $entry, $cookies);
        $response->assertRedirect();
        $path = parse_url($response->headers->get('Location'), PHP_URL_PATH);
        $this->assertMatchesRegularExpression('#^/workspaces/[a-f0-9-]{36}/login$#', $path);
        $loginPage = $this->requestAsBrowser('GET', $path, $cookies);
        $this->assertSame(200, $loginPage->getStatusCode(), 'Unexpected login redirect to '.$loginPage->headers->get('Location').'; cookies: '.implode(', ', array_keys($cookies)));

        return substr($path, 0, -strlen('/login'));
    }

    private function signIn(string $workspace, User $user, array &$cookies, string $password = 'password'): void
    {
        $this->requestAsBrowser('POST', $workspace.'/login', $cookies, [
            'email' => $user->email,
            'password' => $password,
        ])->assertRedirect($workspace.'/dashboard');

        $this->visitDashboard($workspace, $cookies)->assertOk()->assertSee($user->name);
    }

    private function visitDashboard(string $workspace, array &$cookies)
    {
        return $this->requestAsBrowser('GET', $workspace.'/dashboard', $cookies);
    }

    private function requestAsBrowser(string $method, string $uri, array &$cookies, array $data = [])
    {
        // Each request gets a fresh guard, as it would under PHP-FPM.
        Auth::forgetGuards();
        $response = $this->call($method, $uri, $data, $cookies);
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $response;
    }
}
