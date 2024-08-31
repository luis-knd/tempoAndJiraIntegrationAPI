<?php

namespace App\Repository\Eloquent\v1\Jira;

use App\Models\v1\Jira\JiraUser;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Jira\JiraUserRepositoryInterface;

/**
 * Class JiraUserRepository
 *
 * @package   App\Repository\Eloquent\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraUserRepository extends BaseRepository implements JiraUserRepositoryInterface
{
    public function __construct(JiraUser $jiraUser)
    {
        parent::__construct($jiraUser);
    }
}
