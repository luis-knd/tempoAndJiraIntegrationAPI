<?php

namespace App\Models\v1\Jira;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class JiraIssue
 *
 * @package   App\Models\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_issue_id
 * @property mixed $jira_issue_key
 * @property mixed $jira_project_id
 * @property mixed $summary
 * @property mixed $development_category
 * @property mixed $description
 * @property mixed $status
 * @method static updateOrCreate(array $whereCondition, array $parameters)
 */
class JiraIssue extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'jira_issues';
    protected $fillable = [
        'jira_issue_id',
        'jira_issue_key',
        'jira_project_id',
        'summary',
        'development_category',
        'status'
    ];

    public function jiraProjects()
    {
        return $this->belongsTo(JiraProject::class);
    }
}
