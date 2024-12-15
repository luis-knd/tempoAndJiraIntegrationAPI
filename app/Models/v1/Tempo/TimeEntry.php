<?php

namespace App\Models\v1\Tempo;

use App\Models\v1\Jira\JiraIssue;
use App\Models\v1\Jira\JiraUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TimeEntry
 *
 * @package   App\Models\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $tempo_worklog_id
 * @property mixed $jira_issue_id
 * @property mixed $jira_user_id
 * @property mixed $time_spent_in_minutes
 * @property mixed $description
 * @property mixed $entry_created_at
 * @property mixed $entry_updated_at
 * @property mixed $issue
 * @property mixed $jiraUser
 * @method static updateOrCreate(array $whereCondition, array $parameters)
 * @method static where(string $columnName, mixed $value)
 * @method static first()
 */
class TimeEntry extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'time_entries';
    protected $fillable = [
        'tempo_worklog_id',
        'jira_issue_id',
        'jira_user_id',
        'time_spent_in_minutes',
        'description',
        'entry_created_at',
        'entry_updated_at'
    ];

    protected $with = ['issue', 'jiraUser'];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(JiraIssue::class, 'jira_issue_id', 'jira_issue_id');
    }

    public function jiraUser(): BelongsTo
    {
        return $this->belongsTo(JiraUser::class, 'jira_user_id', 'jira_user_id');
    }
}
