<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\SanitizesInput;

class JiraProjectRequest extends BaseRequest
{
    use SanitizesInput;

    protected array $fieldsToStrip = ['jira_project_id', 'jira_project_key', 'name', 'jira_project_category_id'];
    protected array $fieldsToClean = [];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    protected array $publicAttributes = [
        'id' => ['rules' => ['uuid']],
        'jira_project_id' => ['rules' => ['required', 'int', 'unique:jira_projects,jira_project_id']],
        'jira_project_key' => ['rules' => ['required', 'string', 'unique:jira_projects,jira_project_key']],
        'name' => ['rules' => ['required', 'string', 'unique:jira_projects,name']],
        'jira_project_category_id' => ['rules' => [
            'required',
            'int',
            'exists:jira_project_categories,jira_project_category_id'
        ]],
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
            'jira_project_key' => ['rules' => ['required', 'string', 'unique:jira_projects,jira_project_key']],
            'name' => 'required|string|unique:jira_projects,name|max:255',
            'jira_project_category_id' => 'required|int|unique:jira_project_categories,jira_project_category_id',
        ];
    }

    private function rulesForPost(): array
    {
        return [
            'jira_project_id' => 'required|int|unique:jira_projects,jira_project_id',
            'jira_project_key' => ['rules' => ['required', 'string', 'unique:jira_projects,jira_project_key']],
            'name' => 'required|string|unique:jira_projects,name|max:255',
            'jira_project_category_id' => 'required|int|unique:jira_project_categories,jira_project_category_id',
        ];
    }

    private function rulesForDelete(): array
    {
        return [];
    }
}
