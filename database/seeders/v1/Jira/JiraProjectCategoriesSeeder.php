<?php

namespace Database\Seeders\v1\Jira;

use App\Models\v1\Jira\JiraProjectCategory;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JiraProjectCategoriesSeeder extends Seeder
{
    use RefreshDatabase;

    public function run(): void
    {
        JiraProjectCategory::factory()->count(8)->create();
    }
}
