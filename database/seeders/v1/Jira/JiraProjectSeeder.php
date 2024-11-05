<?php

namespace Database\Seeders\v1\Jira;

use App\Models\v1\Jira\JiraProject;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JiraProjectSeeder extends Seeder
{
    use RefreshDatabase;

    public function run(): void
    {
        JiraProject::factory()->count(60)->create();
    }
}
