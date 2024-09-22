<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;

class JiraUserRequest extends BaseRequest
{
    protected array $publicAttributes = [
        'id' => ['rules' => ['uuid']],
        'jira_user_id' => ['rules' => ['required', 'string', 'unique:jira_users,jira_user_id']],
        'name' => ['rules' => ['required', 'string', 'max:255']],
        'email' => ['rules' => ['required', 'email', 'unique:jira_users,email']],
        'jira_user_type' => ['rules' => ['required', 'string']],
        'active' => ['rules' => ['required', 'boolean']],
    ];

    protected array $relations = [
        'jira_teams' => []
    ];

    protected array $proxyFilters = [
        'jira_team.name' => ['mediate' => 'jira_team_name'],
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
            'DELETE' => $this->rulesForDelete(),
            default => [],
        };
    }

    private function rulesForGet(): array
    {
        if (!$this->route('jira_user', false)) {
            return $this->getFilterRules();
        }
        return $this->showRules();
    }

    private function rulesForPost(): array
    {
        return [
            'jira_user_id' => 'required|string|unique:jira_users,jira_user_id',
            'name' => 'required|min:2|max:255',
            'email' => 'required|email|unique:jira_users,email',
            'jira_user_type' => 'required|string',
            'active' => 'required|boolean'
        ];
    }

    private function rulesForDelete(): array
    {
        return [];
    }
}
