<?php

namespace Database\Seeders;

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
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Admin CRM360',
            'email' => 'admin@crm360.com',
            'password' => bcrypt('password'),
        ]);

        \App\Models\GoogleContact::factory(30)->create([
            'user_id' => $user->id,
        ])->each(function ($contact) {
            \App\Models\Training::factory(rand(0, 3))->create([
                'google_contact_id' => $contact->id,
            ]);
        });
    }
}
