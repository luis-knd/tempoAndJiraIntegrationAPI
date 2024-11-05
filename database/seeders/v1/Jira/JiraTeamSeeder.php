<?php

namespace Database\Seeders\v1\Jira;

use App\Models\v1\Jira\JiraTeam;
use Illuminate\Database\Seeder;

class JiraTeamSeeder extends Seeder
{
    public function run(): void
    {
        JiraTeam::factory()->count(3)->create();
    }
}
