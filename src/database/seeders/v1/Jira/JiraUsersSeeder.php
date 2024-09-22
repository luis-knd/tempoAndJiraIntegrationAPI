<?php

namespace Database\Seeders\v1\Jira;

use App\Models\v1\Jira\JiraUser;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JiraUsersSeeder extends Seeder
{
    use RefreshDatabase;

    public function run(): void
    {
        JiraUser::factory()->count(125)->create();
    }
}
