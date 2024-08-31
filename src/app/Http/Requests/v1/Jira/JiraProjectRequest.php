<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;

class JiraProjectRequest extends BaseRequest
{
    protected array $publicAttributes = [
        'id' => ['rules' => ['uuid']],
        'jira_project_id' => ['rules' => ['required', 'string', 'unique:jira_projects,jira_project_id']],
        'name' => ['rules' => ['required', 'string', 'unique:jira_projects,name']],
        'description' => ['rules' => ['nullable', 'string']],
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
        if (!$this->route('jira_project', false)) {
            return $this->getFilterRules();
        }
        return $this->showRules();
    }

    private function rulesForPut(): array
    {
        return [
            'name' => 'required|string|unique:jira_projects,name|max:255',
            'description' => 'nullable|string',
        ];
    }

    private function rulesForPost(): array
    {
        return [
            'jira_project_id' => 'required|string|unique:jira_projects,jira_project_id',
            'name' => 'required|string|unique:jira_projects,name|max:255',
            'description' => 'nullable|string',
        ];
    }

    private function rulesForDelete(): array
    {
        return [];
    }
}
