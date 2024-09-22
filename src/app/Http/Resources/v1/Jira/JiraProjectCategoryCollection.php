<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\BaseResourceCollection;

class JiraProjectCategoryCollection extends BaseResourceCollection
{
    public static $wrap = 'jira_project_categories';
}
