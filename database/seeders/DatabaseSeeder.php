<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->command?->warn('Local development accounts are not seeded outside local/testing environments.');
            return;
        }
        $this->call(LocalTestAccountSeeder::class);
        $this->call(JciCarmonaProjectSeeder::class);
        $this->call(JciCarmonaTaskSeeder::class);
        $this->call(JciCarmonaBudgetSeeder::class);
        $this->call(JciCarmonaContentSeeder::class);
    }
}
