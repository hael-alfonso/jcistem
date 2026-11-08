<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocalTestAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocalTestAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_documented_test_account_is_restored_and_can_sign_in(): void
    {
        $admin = User::factory()->create([
            'name' => 'Chapter Administrator',
            'username' => 'chapter-administrator-legacy',
            'email' => 'jci.admin@gmail.com',
            'role' => 'admin',
            'status' => 'inactive',
            'password' => 'custom-local-password',
        ]);
        $admin->delete();

        $this->seed(LocalTestAccountSeeder::class);

        $admin->refresh();
        $this->assertSame('chapter-admin', $admin->username);
        $this->assertNull($admin->deleted_at);

        foreach ([
            'chapter-admin' => 'admin',
            'chapter-treasurer' => 'treasurer',
            'board-reviewer' => 'bod',
            'chapter-member' => 'member',
        ] as $username => $role) {
            $account = User::where('username', $username)->firstOrFail();
            $this->assertSame($role, $account->role);
            $this->assertSame('active', $account->status);
            $this->assertTrue(Hash::check('password123', $account->password));

            $this->post(route('login.attempt'), ['login' => $username, 'password' => 'password123'])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($account);
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }
}
