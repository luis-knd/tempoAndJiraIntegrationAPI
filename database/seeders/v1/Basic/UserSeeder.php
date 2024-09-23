<?php

namespace Database\Seeders\v1\Basic;

use App\Models\v1\Basic\User;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserSeeder extends Seeder
{
    use RefreshDatabase;

    public function run(): void
    {
        User::factory()->create(
            [
                'name' => 'Luis',
                'lastname' => 'Candelario',
                "email" => "lcandelario@lcandesign.com",
                "password" => bcrypt('password')
            ]
        );
    }
}
