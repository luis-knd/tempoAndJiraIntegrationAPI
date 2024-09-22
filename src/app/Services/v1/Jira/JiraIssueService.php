<?php

namespace App\Services\v1\Jira;

use App\Exceptions\UnprocessableException;
use App\Models\v1\Jira\JiraIssue;
use App\Repository\Interfaces\v1\Jira\JiraIssueRepositoryInterface;
use App\Services\ProcessParamsTraits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JsonException;

/**
 * Class JiraIssueService
 *
 * @package   App\Services\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraIssueService
{
    use ProcessParamsTraits;

    public function __construct(readonly JiraIssueRepositoryInterface $jiraIssueRepository)
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
        $issues = $this->process($params);
        return $this->jiraIssueRepository->findByParams(
            $issues['filter'],
            $issues['with'],
            $issues['order'],
            $issues['page']
        );
    }

    public function make(array $params): JiraIssue
    {
        $jiraIssue = new JiraIssue();
        $this->setParams($params, $jiraIssue);
        $jiraIssue->save();
        return $jiraIssue;
    }

    private function setParams(array $params, JiraIssue $jiraIssue): void
    {
        $jiraIssue->jira_issue_id = $params['jira_issue_id'];
        $jiraIssue->jira_issue_key = $params['jira_issue_key'];
        $jiraIssue->jira_project_id = $params['jira_project_id'];
        $jiraIssue->summary = $params['summary'];
        $jiraIssue->development_category = $params['development_category'];
        $jiraIssue->status = $params['status'];
    }

    /**
     * load
     *
     * @param JiraIssue $jiraIssue
     * @param array     $params
     * @return JiraIssue
     * @throws UnprocessableException
     * @throws JsonException
     */
    public function load(JiraIssue $jiraIssue, array $params = []): JiraIssue
    {
        $issues = $this->process($params);
        if ($issues['with']) {
            $jiraIssue->load($issues['with']);
        }
        return $jiraIssue;
    }

    public function update(JiraIssue $jiraIssue, array $params): JiraIssue
    {
        $jiraIssue->update($params);
        $jiraIssue->save();
        return $jiraIssue;
    }

    public function delete(JiraIssue $jiraIssue): ?bool
    {
        return $jiraIssue->delete();
    }
}
