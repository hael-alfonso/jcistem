<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Mich Alfonso', 'email' => 'admin@jcicarmona.org', 'role' => 'admin', 'member_no' => 'JCI-001'],
            ['name' => 'Carlo Mendoza', 'email' => 'treasurer@jcicarmona.org', 'role' => 'treasurer', 'member_no' => 'JCI-004'],
            ['name' => 'Juan Dela Cruz', 'email' => 'bod@jcicarmona.org', 'role' => 'bod', 'member_no' => 'JCI-002'],
            ['name' => 'Ana Cruz', 'email' => 'member@jcicarmona.org', 'role' => 'member', 'member_no' => 'JCI-006'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'member_no' => $account['member_no'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
