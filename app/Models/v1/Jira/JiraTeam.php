<?php

namespace App\Models\v1\Jira;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class JiraTeam
 *
 * @package   App\Models\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_team_id
 * @property mixed $name
 */
class JiraTeam extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'jira_teams';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'jira_team_id',
        'name',
    ];

    public function jiraUsers(): BelongsToMany
    {
        return $this->belongsToMany(JiraUser::class, 'jira_team_jira_user', 'jira_team_id', 'jira_user_id');
    }
}
