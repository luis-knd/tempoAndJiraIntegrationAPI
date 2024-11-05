<?php

namespace Database\Factories\v1\Jira;

use App\Models\v1\Jira\JiraTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class JiraTeamFactory
 *
 * @package   Database\Factories\v1\Jira
 * @copyright 11-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraTeamFactory extends Factory
{
    protected $model = JiraTeam::class;
    private static array $jiraTeams = [
        'developer', 'functional_analyst', 'quality_assurance', 'scrum_master', 'product_owner'
    ];

    public function definition(): array
    {
        return [
            'jira_team_id' => $this->faker->randomNumber(6, true) . ":" . $this->faker->uuid(),
            'name' => self::$jiraTeams[array_rand(self::$jiraTeams)]
        ];
    }
}
