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

    public function test_test_accounts_have_documented_usernames_and_existing_passwords_survive_reseeding(): void
    {
        $admin = User::factory()->create([
            'name' => 'Chapter Administrator',
            'username' => 'chapter-administrator-legacy',
            'email' => 'jci.admin@gmail.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'custom-local-password',
        ]);

        $this->seed(LocalTestAccountSeeder::class);

        $admin->refresh();
        $this->assertSame('chapter-admin', $admin->username);
        $this->assertTrue(Hash::check('custom-local-password', $admin->password));
        foreach (['chapter-treasurer' => 'treasurer', 'board-reviewer' => 'bod', 'chapter-member' => 'member'] as $username => $role) {
            $this->assertDatabaseHas('users', ['username' => $username, 'role' => $role, 'status' => 'active']);
        }
    }
}
