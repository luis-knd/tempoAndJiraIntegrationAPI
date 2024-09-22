<?php

namespace Database\Factories\v1\Jira;

use App\Models\v1\Jira\JiraProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class JiraProjectCategoryFactory
 *
 * @package   Database\Factories\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraProjectCategoryFactory extends Factory
{
    protected $model = JiraProjectCategory::class;
    protected static int $jiraCategoryId = 10000;

    public function definition(): array
    {
        return [
            'jira_category_id' => self::$jiraCategoryId++,
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
        ];
    }
}
