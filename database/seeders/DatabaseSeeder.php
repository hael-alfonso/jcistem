<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->command?->warn('Local development accounts are not seeded outside local/testing environments.');
            return;
        }
        $accounts = [
            ['name' => 'Chapter Administrator', 'email' => 'jci.admin@gmail.com', 'role' => 'admin', 'member_no' => 'JCI-001', 'concept_reviewer' => true, 'proposal_reviewer' => false],
            ['name' => 'Chapter Treasurer', 'email' => 'jci.treasurer@gmail.com', 'role' => 'treasurer', 'member_no' => 'JCI-002', 'concept_reviewer' => false, 'proposal_reviewer' => false],
            ['name' => 'Board Reviewer', 'email' => 'jci.bod@gmail.com', 'role' => 'bod', 'member_no' => 'JCI-003', 'concept_reviewer' => false, 'proposal_reviewer' => true],
            ['name' => 'Chapter Member', 'email' => 'jci.member@gmail.com', 'role' => 'member', 'member_no' => 'JCI-004', 'concept_reviewer' => false, 'proposal_reviewer' => false],
        ];
        foreach ($accounts as $account) {
            User::firstOrCreate(['email' => $account['email']], $account + [
                'password' => env('JCI_BOOTSTRAP_PASSWORD', 'password123'),
                'status' => 'active', 'email_verified_at' => now(),
            ]);
        }
        $this->call(JciCarmonaProjectSeeder::class);
        $this->call(JciCarmonaTaskSeeder::class);
        $this->call(JciCarmonaBudgetSeeder::class);
    }
}
