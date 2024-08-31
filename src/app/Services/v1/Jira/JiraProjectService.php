<?php

namespace App\Services\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraProject;
use App\Repository\Interfaces\v1\Jira\JiraProjectRepositoryInterface;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class JiraProjectService
 *
 * @package   App\Services\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraProjectService
{
    use ProcessParamsTraits;

    public function __construct(readonly JiraProjectRepositoryInterface $jiraProjectRepository)
    {
    }

    /**
     *  index
     *
     * @param array $params
     * @return LengthAwarePaginator
     * @throws UnprocessableException
     */
    public function index(array $params): LengthAwarePaginator
    {
        $projects = $this->process($params);
        return $this->jiraProjectRepository->findByParams(
            $projects['filter'],
            $projects['with'],
            $projects['order'],
            $projects['page']
        );
    }

    public function make(array $params): JiraProject
    {
        $jiraProject = new JiraProject();
        $this->setParams($params, $jiraProject);
        $jiraProject->save();
        return $jiraProject;
    }

    private function setParams(array $params, JiraProject $jiraProject): void
    {
        $jiraProject->jira_project_id = $params['jira_project_id'];
        $jiraProject->name = $params['name'];
        $jiraProject->description = $params['description'];
    }

    /**
     *  load
     *
     * @param JiraProject $jiraProject
     * @param array       $params
     * @return JiraProject
     * @throws UnprocessableException
     */
    public function load(JiraProject $jiraProject, array $params = []): JiraProject
    {
        $projects = $this->process($params);
        if ($projects['with']) {
            $jiraProject->load($projects['with']);
        }
        return $jiraProject;
    }

    public function update(JiraProject $jiraProject, array $params): JiraProject
    {
        $jiraProject->update($params);
        $jiraProject->save();
        return $jiraProject;
    }

    public function delete(JiraProject $jiraProject): ?bool
    {
        return $jiraProject->delete();
    }
}
