<?php

namespace App\Models\v1\Jira;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class JiraIssue
 *
 * @package   App\Models\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $jira_issue_id
 * @property mixed $summary
 * @property mixed $development_category
 * @property mixed $description
 * @property mixed $status
 */
class JiraIssue extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'jira_issues';
    protected $fillable = [
        'jira_issue_id',
        'summary',
        'development_category',
        'description',
        'status'
    ];
}
