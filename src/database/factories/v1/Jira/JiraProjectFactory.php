<?php

namespace Database\Factories\v1\Jira;

use App\Models\v1\Jira\JiraProject;
use App\Models\v1\Jira\JiraProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class JiraProjectFactory
 *
 * @package   Database\Factories\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraProjectFactory extends Factory
{
    protected $model = JiraProject::class;
    protected static int $jiraProjectId = 11100;

    public function definition(): array
    {
        return [
            'jira_project_id' => self::$jiraProjectId++,
            'jira_project_key' => $this->faker->unique()->countryISOAlpha3,
            'name' => $this->faker->word,
            //@phpstan-ignore-next-line
            'jira_project_category_id' => JiraProjectCategory::factory()->create()->jira_category_id
        ];
    }
}
