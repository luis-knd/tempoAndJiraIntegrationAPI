<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;

class JiraProjectCategoryRequest extends BaseRequest
{
    protected array $publicAttributes = [
        'id' => ['rules' => ['uuid']],
        'name' => ['rules' => ['string', 'max:255']],
        'description' => ['rules' => ['string']],
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
            'POST', 'PUT' => [
                'jira_category_id' => 'required|unique:jira_project_categories,jira_category_id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
            default => [],
        };
    }
}
