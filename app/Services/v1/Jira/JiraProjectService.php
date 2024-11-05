<?php

namespace App\Services\v1\Jira;

use App\Exceptions\BadRequestException;
use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraProject;
use App\Repository\Eloquent\v1\Jira\JiraProjectRepository;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JsonException;

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

    public function __construct(readonly JiraProjectRepository $jiraProjectRepository)
    {
    }

    /**
     *  index
     *
     * @param array $params
     * @return LengthAwarePaginator
     * @throws UnprocessableException
     * @throws JsonException
     * @throws BadRequestException
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
        $jiraProject->jira_project_key = $params['jira_project_key'];
        $jiraProject->jira_project_category_id = $params['jira_project_category_id'];
    }

    /**
     * load
     *
     * @param JiraProject $jiraProject
     * @param array       $params
     * @return JiraProject
     * @throws UnprocessableException
     * @throws JsonException
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
