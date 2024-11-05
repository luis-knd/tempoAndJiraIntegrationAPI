<?php

namespace App\Models\v1\Jira;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class JiraProject
 *
 * @package   App\Models\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_project_id
 * @property mixed $name
 * @property mixed $jira_project_key
 * @property mixed $jira_project_category_id
 * @method static updateOrCreate(array $whereCondition, array $parameters)
 * @method static where(string $string, mixed $jira_project_id)
 * @method static first()
 */
class JiraProject extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'jira_projects';
    protected $fillable = [
        'jira_project_id',
        'name',
        'jira_project_key',
        'jira_project_category_id'
    ];

    protected $with = ['jiraProjectCategory'];

    public function jiraIssues(): HasMany
    {
        return $this->hasMany(JiraIssue::class, 'jira_project_id', 'jira_project_id');
    }

    public function jiraProjectCategory(): BelongsTo
    {
        return $this->belongsTo(JiraProjectCategory::class, 'jira_project_category_id', 'jira_category_id');
    }
}
