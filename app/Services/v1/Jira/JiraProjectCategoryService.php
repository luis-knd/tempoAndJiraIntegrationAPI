<?php

namespace App\Services\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraProjectCategory;
use App\Repository\Eloquent\v1\Jira\JiraProjectCategoryRepository;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JsonException;

/**
 * Class JiraProjectCategoryService
 *
 * @package   App\Services\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraProjectCategoryService
{
    use ProcessParamsTraits;

    public function __construct(readonly JiraProjectCategoryRepository $jiraProjectCategoryRepository)
    {
    }

    public function index(array $params): LengthAwarePaginator
    {
        $users = $this->process($params);
        return $this->jiraProjectCategoryRepository->findByParams(
            $users['filter'],
            $users['with'],
            $users['order'],
            $users['page']
        );
    }

    public function make(array $params): JiraProjectCategory
    {
        $jiraProjectCategory = new JiraProjectCategory();
        $this->setParams($params, $jiraProjectCategory);
        $jiraProjectCategory->save();
        return $jiraProjectCategory;
    }

    private function setParams(array $params, JiraProjectCategory $jiraProjectCategory): void
    {
        $jiraProjectCategory->name = $params['name'];
        $jiraProjectCategory->description = $params['description'];
        $jiraProjectCategory->jira_project_category_id = $params['jira_project_category_id'];
    }

    /**
     *  load
     *
     * @param JiraProjectCategory $jiraProjectCategory
     * @param array               $params
     * @return JiraProjectCategory
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function load(JiraProjectCategory $jiraProjectCategory, array $params): JiraProjectCategory
    {
        $jiraProjectCategories = $this->process($params);
        if ($jiraProjectCategories['with']) {
            $jiraProjectCategory->load($jiraProjectCategories['with']);
        }
        return $jiraProjectCategory;
    }

    public function update(JiraProjectCategory $jiraProjectCategory, array $params): JiraProjectCategory
    {
        $jiraProjectCategory->update($params);
        $jiraProjectCategory->save();
        return $jiraProjectCategory;
    }

    public function delete(JiraProjectCategory $jiraProjectCategory): ?bool
    {
        return $jiraProjectCategory->delete();
    }
}