<?php

namespace App\Models\v1\Jira;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class JiraUser
 *
 * @package   App\Models\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed   $id
 * @property mixed   $jira_user_id
 * @property mixed   $name
 * @property mixed   $email
 * @property mixed   $jira_user_type
 * @property boolean $active
 * @method static updateOrCreate(array $whereCondition, array $parameters)
 * @method static first()
 */
class JiraUser extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'jira_users';

    protected $with = ['jiraTeams'];
    protected $fillable = [
        'jira_user_id',
        'name',
        'email',
        'jira_user_type',
        'active'
    ];

    public function jiraTeams(): BelongsToMany
    {
        return $this->belongsToMany(JiraTeam::class, 'jira_team_jira_user', 'jira_user_id', 'jira_team_id');
    }

    protected static function booted(): void
    {
        static::deleted(static function (JiraUser $jiraUser) {
            $jiraUser->jiraTeams()->detach();
        });
    }
}
