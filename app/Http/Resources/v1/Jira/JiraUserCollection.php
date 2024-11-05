<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\BaseResourceCollection;

/**
 * Class JiraUserCollection
 *
 * @package   App\Http\Resources\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraUserCollection extends BaseResourceCollection
{
    public static $wrap = 'jira_users';
}
