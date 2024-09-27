<?php

namespace Database\Seeders\v1\Jira;

use App\Models\v1\Jira\JiraIssue;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JiraIssueSeeder extends Seeder
{
    use RefreshDatabase;

    public function run(): void
    {
        JiraIssue::factory()->count(85)->create();
    }
}
