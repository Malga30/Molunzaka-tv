<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Create primary profile for each user
            Profile::factory()->primary()->create([
                'user_id' => $user->id,
                'name' => $user->name . "'s Profile",
            ]);

            // Create additional profiles for some users
            if (rand(0, 1)) {
                Profile::factory()->count(rand(1, 3))->create([
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
