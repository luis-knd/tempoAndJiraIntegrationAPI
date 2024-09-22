<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\BaseResourceCollection;

/**
 * Class JiraTeamCollection
 *
 * @package App\Http\Resources\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraTeamCollection extends BaseResourceCollection
{
    public static $wrap = 'jira_teams';
}
