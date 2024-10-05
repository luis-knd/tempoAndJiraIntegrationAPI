<?php

namespace App\Http\Requests\v1\Tempo;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\SanitizesInput;

/**
 * Class TimeEntryRequest
 *
 * @package   App\Http\Requests\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TimeEntryRequest extends BaseRequest
{
    use SanitizesInput;

    protected array $fieldsToStrip = [
        'tempo_worklog_id',
        'jira_issue_id',
        'jira_user_id',
        'time_spent_in_minutes',
        'entry_created_at',
        'entry_updated_at'
    ];
    protected array $fieldsToClean = ['description'];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    protected array $publicAttributes = [
        'tempo_worklog_id' => ['rules' => ['required', 'integer', 'min:0']],
        'jira_issue_id' => ['rules' => ['required', 'integer', 'min:0', 'exists:jira_issues,jira_issue_id']],
        'jira_user_id' => ['rules' => ['required', 'string', 'exists:jira_users,jira_user_id']],
        'time_spent_in_minutes' => ['rules' => ['required', 'integer', 'min:0']],
        'description' => ['rules' => ['required', 'string']],
        'entry_created_at' => ['rules' => ['required', 'date']],
        'entry_updated_at' => ['rules' => ['required', 'date']],
    ];

    protected array $relations = [
        'jira_issues' => [],
        'jira_users' => [],
    ];

    protected array $proxyFilters = [
        'jira_issues.summary' => ['mediate' => 'jira_issues_summary'],
        'jira_users.name' => ['mediate' => 'jira_users_name'],
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

    private function rulesForPost(): array
    {
        return [
            'tempo_worklog_id' => 'required|numeric|min:0',
            'jira_issue_id' => 'required|numeric|min:0|exists:jira_issues,jira_issue_id',
            'jira_user_id' => 'required|numeric|min:0|exists:jira_users,jira_user_id',
            'time_spent_in_minutes' => 'required|numeric|min:0',
            'description' => 'required|string',
            'entry_created_at' => 'required|date',
            'entry_updated_at' => 'required|date',
        ];
    }
}
