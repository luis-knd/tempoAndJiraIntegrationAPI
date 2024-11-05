<?php

namespace App\Repository\Eloquent\v1\Jira;

use App\Models\v1\Jira\JiraProject;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Jira\JiraProjectRepositoryInterface;

/**
 * Class JiraProjectRepository
 *
 * @package   App\Repository\Eloquent\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraProjectRepository extends BaseRepository implements JiraProjectRepositoryInterface
{
    public function __construct(JiraProject $jiraProject)
    {
        parent::__construct($jiraProject);
    }
}
