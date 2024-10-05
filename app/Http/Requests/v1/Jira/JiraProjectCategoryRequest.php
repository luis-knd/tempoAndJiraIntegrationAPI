<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\SanitizesInput;

class JiraProjectCategoryRequest extends BaseRequest
{
    use SanitizesInput;

    protected array $fieldsToStrip = ['name', 'jira_category_id'];
    protected array $fieldsToClean = ['description'];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    protected array $publicAttributes = [
        'name' => ['rules' => ['string', 'max:255']],
        'description' => ['rules' => ['string']],
        'jira_category_id' => ['rules' => ['required', 'exists:jira_project_categories,jira_category_id']]
    ];

    protected array $relations = [
        'projects' => []
    ];

    protected array $proxyFilters = [
        'projects.name' => [
            'rules' => ['string', 'max:255'],
            'mediate' => 'project_name',
        ],
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->getMethod()) {
            'GET' => $this->rulesForGet(),
            'POST' => $this->rulesForPost(),
            'PUT' => $this->rulesForPut(),
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

    private function rulesForPost(): array
    {
        return [
            'jira_category_id' => 'required|int|unique:jira_project_categories,jira_category_id',
            'name' => 'required|string|max:255|unique:jira_project_categories,name',
            'description' => 'nullable|string',
        ];
    }

    private function rulesForPut(): array
    {
        return [
            'name' => 'required|string|max:255|unique:jira_project_categories,name',
            'description' => 'nullable|string',
        ];
    }
}
