<?php

namespace App\Repository\Eloquent\v1\Jira;

use App\Models\v1\Jira\JiraIssue;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Jira\JiraTeamRepositoryInterface;

/**
 * Class JiraIssueRepository
 *
 * @package   App\Providers
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraIssueRepository extends BaseRepository implements JiraTeamRepositoryInterface
{
    public function __construct(JiraIssue $jiraIssue)
    {
        parent::__construct($jiraIssue);
    }
}
