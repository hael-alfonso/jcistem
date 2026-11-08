<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class LocalTestAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->command?->warn('Local test accounts are available only in local/testing environments.');
            return;
        }

        $accounts = [
            ['name' => 'Chapter Administrator', 'username' => 'chapter-admin', 'email' => 'jci.admin@gmail.com', 'role' => 'admin', 'member_no' => 'JCI-001', 'concept_reviewer' => true, 'proposal_reviewer' => false],
            ['name' => 'Chapter Treasurer', 'username' => 'chapter-treasurer', 'email' => 'jci.treasurer@gmail.com', 'role' => 'treasurer', 'member_no' => 'JCI-002', 'concept_reviewer' => false, 'proposal_reviewer' => false],
            ['name' => 'Board Reviewer', 'username' => 'board-reviewer', 'email' => 'jci.bod@gmail.com', 'role' => 'bod', 'member_no' => 'JCI-003', 'concept_reviewer' => false, 'proposal_reviewer' => true],
            ['name' => 'Chapter Member', 'username' => 'chapter-member', 'email' => 'jci.member@gmail.com', 'role' => 'member', 'member_no' => 'JCI-004', 'concept_reviewer' => false, 'proposal_reviewer' => false],
        ];

        $password = (string) env('JCI_BOOTSTRAP_PASSWORD', 'password123');
        if (mb_strlen($password) < 10) {
            throw new RuntimeException('JCI_BOOTSTRAP_PASSWORD must contain at least 10 characters.');
        }

        foreach ($accounts as $account) {
            $user = User::withTrashed()->firstOrNew(['email' => $account['email']]);
            $usernameOwner = User::withTrashed()
                ->where('username', $account['username'])
                ->when($user->exists, fn ($query) => $query->whereKeyNot($user->getKey()))
                ->first();

            if ($usernameOwner) {
                throw new RuntimeException("The local test username {$account['username']} is already assigned to another account.");
            }

            // Local demo accounts are deterministic: every seed restores their
            // documented identity, access status, role, and configured password.
            $user->forceFill($account + [
                'password' => $password,
                'status' => 'active',
                'email_verified_at' => now(),
                'deleted_at' => null,
                'remember_token' => null,
                'active_login_token' => null,
                'active_login_seen_at' => null,
            ])->save();
        }
    }
}
