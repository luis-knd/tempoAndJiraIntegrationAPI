<?php

namespace App\Http\Resources\v1\Tempo;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Http\JsonResponse;
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
 */
class TimeEntryResource extends JsonResource
{
    use FieldsResourceTraits;

    public static function toJsonResponse(TimeEntry $timeEntry): JsonResponse
    {
        return jsonResponse(['time_entries' => self::make($timeEntry)]);
    }

    public function toArray($request): array
    {
        return [
            'tempo_worklog_id' => $this->tempo_worklog_id,
            'jira_issue_id' => $this->jira_issue_id,
            'jira_user_id' => $this->jira_user_id,
            'time_spent_in_minutes' => $this->time_spent_in_minutes,
            'description' => $this->description,
            'entry_created_at' => $this->entry_created_at,
            'entry_updated_at' => $this->entry_updated_at
        ];
    }
}
