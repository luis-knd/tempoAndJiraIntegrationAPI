<?php

namespace App\Http\Resources\v1\Tempo;

use App\Http\Resources\FieldsResourceTraits;
use App\Http\Resources\v1\Jira\JiraIssueResource;
use App\Http\Resources\v1\Jira\JiraUserResource;
use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TimeEntryResource
 *
 * @package   App\Http\Resources\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $tempo_worklog_id
 * @property mixed $jira_issue_id
 * @property mixed $jira_user_id
 * @property mixed $time_spent_in_minutes
 * @property mixed $description
 * @property mixed $entry_created_at
 * @property mixed $entry_updated_at
 * @property mixed $issue
 * @property mixed $jiraUser
 * @method relationLoaded(string $string)
 */
class TimeEntryResource extends JsonResource
{
    use FieldsResourceTraits;

    public static function toJsonResponse(TimeEntry $timeEntry): JsonResponse
    {
        return jsonResponse(['time_entries' => self::make($timeEntry)]);
    }

    public function toArray(Request $request): array
    {
        $this->init($request);
        $jiraIssue = $this->getJiraIssueData();
        $jiraUser = $this->getJiraUserData();
        return [
            'tempo_worklog_id' => $this->tempo_worklog_id,
            'issue' => $jiraIssue,
            'user' => $jiraUser,
            'time_spent_in_minutes' => $this->time_spent_in_minutes,
            'description' => $this->description,
            'entry_created_at' => $this->entry_created_at,
            'entry_updated_at' => $this->entry_updated_at
        ];
    }

    public function getJiraIssueData(): mixed
    {
        return $this->when(
            $this->relationLoaded('issue') && $this->depthLevel(),
            function () {
                return JiraIssueResource::make($this->issue)
                    ->setLevel($this->level + 1)
                    ->setMaxLevel($this->maxLevel)
                    ->setPossibleTransitions(false);
            },
            [
                'jira_issue_id' => $this->jira_issue_id
            ]
        );
    }

    public function getJiraUserData(): mixed
    {
        return $this->when(
            $this->relationLoaded('jiraUser') && $this->depthLevel(),
            function () {
                return JiraUserResource::make($this->jiraUser)
                    ->setLevel($this->level + 1)
                    ->setMaxLevel($this->maxLevel)
                    ->setPossibleTransitions(false);
            },
            [
                'jira_user_id' => $this->jira_user_id
            ]
        );
    }
}
