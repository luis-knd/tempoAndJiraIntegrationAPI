<?php

namespace App\Repository\Eloquent\v1\Jira;

use App\Models\v1\Jira\JiraProjectCategory;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Jira\JiraProjectCategoryRepositoryInterface;

/**
 * Class JiraProjectCategoryRepository
 *
 * @package   App\Repository\Eloquent\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraProjectCategoryRepository extends BaseRepository implements JiraProjectCategoryRepositoryInterface
{
    public function __construct(JiraProjectCategory $model)
    {
        parent::__construct($model);
    }
}
