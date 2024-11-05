<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\BaseResourceCollection;

class JiraProjectCollection extends BaseResourceCollection
{
    public static $wrap = 'jira_projects';
}
