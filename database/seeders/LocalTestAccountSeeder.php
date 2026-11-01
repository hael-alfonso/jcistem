<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(['email' => $account['email']], $account + [
                'password' => env('JCI_BOOTSTRAP_PASSWORD', 'password123'),
                'status' => 'active', 'email_verified_at' => now(),
            ]);

            // Repair legacy local usernames while keeping existing passwords and roles.
            if ($user->name === $account['name'] && $user->role === $account['role']
                && $user->username !== $account['username']
                && !User::where('username', $account['username'])->exists()) {
                $user->update(['username' => $account['username']]);
            }
        }
    }
}
