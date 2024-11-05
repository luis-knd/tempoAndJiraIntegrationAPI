<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\SanitizesInput;

/**
 * Class JiraTeamRequest
 *
 * @package App\Http\Requests\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author Luis Candelario <lcandelario@lcandesign.com>
 */
class JiraTeamRequest extends BaseRequest
{
    use SanitizesInput;

    protected array $fieldsToStrip = ['jira_team_id', 'name'];
    protected array $fieldsToClean = [];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    protected array $publicAttributes = [
        'id' => ['uuid'],
        'jira_team_id' => ['required', 'uuid', 'unique:jira_teams,jira_team_id'],
        'name' => ['required', 'string', 'max:255'],
    ];

    protected array $relations = [
        'jira_users' => []
    ];

    protected array $proxyFilters = [
        'jira_user.name' => ['mediate' => 'jira_user_name'],
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
        if (!$this->route('jira_team', false)) {
            return $this->getFilterRules();
        }
        return $this->showRules();
    }

    private function rulesForPut(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    private function rulesForPost(): array
    {
        return [
            'jira_team_id' => 'required|uuid|unique:jira_teams,jira_team_id',
            'name' => 'required|string|max:255',
        ];
    }

    private function rulesForDelete(): array
    {
        return [];
    }
}
