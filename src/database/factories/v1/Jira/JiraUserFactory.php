<?php

namespace Database\Factories\v1\Jira;

use App\Models\v1\Jira\JiraUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class JiraProjectCategoryFactory
 *
 * @package   Database\Factories\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraUserFactory extends Factory
{
    protected $model = JiraUser::class;
    private static array $jiraUserType = ['atlassian', 'app', 'customer'];

    public function definition(): array
    {
        return [
            'jira_user_id' => $this->faker->randomNumber(6, true) . ":" . $this->faker->uuid(),
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'email' => $this->faker->randomNumber(9) . $this->faker->email,
            'jira_user_type' => self::$jiraUserType[array_rand(self::$jiraUserType)],
            'active' => $this->faker->boolean
        ];
    }
}
