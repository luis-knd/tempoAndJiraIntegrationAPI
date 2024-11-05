<?php

namespace Database\Factories\v1\Tempo;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Jira\JiraUser;
use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class TimeEntryFactory
 *
 * @package   Database\Factories\v1\Tempo
 * @copyright 10-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    public function definition(): array
    {
        return [
            'tempo_worklog_id' => $this->faker->unique()->randomNumber(6),
            'jira_issue_id' => function () {
                /** @var JiraIssue $jiraIssue */
                $jiraIssue = JiraIssue::factory()->create();
                return $jiraIssue->jira_issue_id;
            },
            'jira_user_id' => function () {
                /** @var JiraUser $jiraUser */
                $jiraUser = JiraUser::factory()->create();
                return $jiraUser->jira_user_id;
            },
            'time_spent_in_minutes' => $this->faker->numberBetween(15, 480),
            'description' => $this->faker->sentence(),
            'entry_created_at' => $this->faker->dateTimeBetween('-1 year'),
            'entry_updated_at' => $this->faker->dateTimeBetween('-1 year')
        ];
    }
}
