<?php

namespace App\Repository\Eloquent\v1\Jira;

use App\Models\v1\Jira\JiraTeam;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Jira\JiraTeamRepositoryInterface;

/**
 * Class JiraTeamRepository
 *
 * @package   App\Repository\Eloquent\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraTeamRepository extends BaseRepository implements JiraTeamRepositoryInterface
{
    public function __construct(JiraTeam $jiraTeam)
    {
        parent::__construct($jiraTeam);
    }
}
