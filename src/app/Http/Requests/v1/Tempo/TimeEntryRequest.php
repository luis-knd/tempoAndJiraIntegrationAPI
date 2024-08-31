<?php

namespace App\Http\Requests\v1\Tempo;

use App\Http\Requests\BaseRequest;

/**
 * Class TimeEntryRequest
 *
 * @package   App\Http\Requests\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TimeEntryRequest extends BaseRequest
{
    protected array $publicAttributes = [
        'id' => ['rules' => ['uuid']],
        'date' => ['rules' => ['required', 'date']],
        'hours' => ['rules' => ['required', 'numeric', 'min:0']],
        'description' => ['rules' => ['required', 'string', 'max:255']],
        'issue_id' => ['rules' => ['required', 'uuid', 'exists:issues,id']],
        'tempo_user_id' => ['rules' => ['required', 'uuid', 'exists:tempo_users,id']],
    ];

    protected array $relations = [
        'jira_issues' => [],
        'tempo_users' => [],
    ];

    protected array $proxyFilters = [
        'jira_issues.summary' => ['mediate' => 'jira_issues_summary'],
        'tempo_users.name' => ['mediate' => 'tempo_users_name'],
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
        if (!$this->route('time_entry', false)) {
            return $this->getFilterRules();
        }
        return $this->showRules();
    }

    private function rulesForPut(): array
    {
        return [
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
        ];
    }

    private function rulesForPost(): array
    {
        return [
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'issue_id' => 'required|uuid|exists:issues,id',
            'tempo_user_id' => 'required|uuid|exists:tempo_users,id',
        ];
    }

    private function rulesForDelete(): array
    {
        return [];
    }
}
