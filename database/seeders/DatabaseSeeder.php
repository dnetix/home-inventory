<?php

namespace Database\Seeders;

use App\Models\Home;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Diego Calle',
            'email' => 'dnetix@gmail.com',
        ]);

        $home = Home::create(['name' => "Diego's Home"]);
        $home->users()->attach($user, ['role' => 'owner']);
        $user->update(['current_home_id' => $home->id]);

        $this->call(DemoSeeder::class);
    }
}
