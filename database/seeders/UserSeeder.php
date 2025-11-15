<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@molunzaka.test',
            'password' => bcrypt('password'),
        ]);

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@molunzaka.test',
            'password' => bcrypt('password'),
        ]);

        // Create creator user
        User::factory()->creator()->create([
            'name' => 'Creator User',
            'email' => 'creator@molunzaka.test',
            'password' => bcrypt('password'),
        ]);

        // Create additional random users
        User::factory()->count(5)->create();
    }
}
