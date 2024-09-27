<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;

class JiraIssueRequest extends BaseRequest
{
    protected array $publicAttributes = [
        'id' => ['rules' => ['uuid']],
        'jira_issue_id' => ['rules' => ['required', 'string', 'unique:jira_issues,jira_issue_id']],
        'jira_issue_key' => ['rules' => ['required', 'string', 'unique:jira_issues,jira_issue_key']],
        'project' => ['rules' => ['required', 'int', 'unique:jira_projects,jira_project_id']],
        'summary' => ['rules' => ['required', 'string', 'max:255']],
        'development_category' => ['rules' => ['required', 'string', 'max:255']],
        'status' => ['rules' => ['required', 'max:255']]
    ];

    protected array $relations = [
        'jira_projects' => []
    ];

    protected array $proxyFilters = [
        'jira_projects.jira_project_id' => ['mediate' => 'jira_projects_jira_project_id']
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->getMethod()) {
            'GET' => $this->rulesForGet(),
            'PUT' => $this->rulesForPut(),
            'POST' => $this->rulesForPost(),
            'DELETE' => $this->rulesForDelete(),
            default => [],
        };
    }

    private function rulesForGet(): array
    {
        if (!$this->route('jira_issue', false)) {
            return $this->getFilterRules();
        }
        return $this->showRules();
    }

    private function rulesForPut(): array
    {
        return [
            'summary' => 'required|string|max:255',
            'development_category' => 'nullable|string',
            'status' => 'required|string'
        ];
    }

    private function rulesForPost(): array
    {
        return [
            'jira_issue_id' => 'required|string|unique:jira_issues,jira_issue_id',
            'jira_issue_key' => 'required|string|unique:jira_issues,jira_issue_key',
            'jira_project_id' => 'required|int|unique:jira_projects,jira_project_id',
            'summary' => 'required|string|max:255',
            'development_category' => 'nullable|string',
            'status' => 'required|string'
        ];
    }

    private function rulesForDelete(): array
    {
        return [];
    }
}
