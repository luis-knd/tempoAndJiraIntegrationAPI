<?php

namespace App\Http\Requests\v1\Auth\v1\Tempo;

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

    /**
     * @var string[]
     */
    protected array $fieldsToStrip = [
        'tempo_worklog_id',
        'jira_issue_id',
        'jira_user_id',
        'time_spent_in_minutes',
        'entry_created_at',
        'entry_updated_at',
    ];

    /**
     * @var string[]
     */
    protected array $fieldsToClean = ['description'];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    /**
     * Atributos públicos y sus reglas de validación.
     *
     * @var array<string, array{rules: string[]}>
     */
    protected array $publicAttributes = [
        'tempo_worklog_id' => ['rules' => ['required', 'integer', 'min:0']],
        'jira_issue_id' => ['rules' => ['required', 'integer', 'min:0', 'exists:jira_issues,jira_issue_id']],
        'jira_user_id' => ['rules' => ['required', 'string', 'exists:jira_users,jira_user_id']],
        'time_spent_in_minutes' => ['rules' => ['required', 'integer', 'min:0']],
        'description' => ['rules' => ['required', 'string']],
        'entry_created_at' => ['rules' => ['required', 'date']],
        'entry_updated_at' => ['rules' => ['required', 'date']],
    ];

    /**
     * Relaciones asociadas.
     *
     * @var array<string, array>
     */
    protected array $relations = [
        'issue' => [],
        'jira_user' => [],
    ];

    /**
     * Filtros de proxy.
     *
     * @var array<string, array{mediate: string}>
     */
    protected array $proxyFilters = [
        'jira_issues.summary' => ['mediate' => 'jira_issues_summary'],
        'jira_users.name' => ['mediate' => 'jira_users_name'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     *  rules
     *
     * @return array|string[]
     */
    public function rules(): array
    {
        return match ($this->getMethod()) {
            'GET' => $this->rulesForGet(),
            'POST' => $this->rulesForPost(),
            'PUT' => $this->rulesForPut(),
            default => [],
        };
    }

    /**
     *  rulesForGet
     *
     * @return array<string>
     */
    private function rulesForGet(): array
    {
        if (!$this->route('time_entry', false)) {
            return $this->getFilterRules();
        }
        return $this->showRules();
    }

    /**
     *  rulesForPost
     *
     * @return string[]
     */
    private function rulesForPost(): array
    {
        return [
            'tempo_worklog_id' => 'required|unique:time_entries,tempo_worklog_id|numeric|min:0',
            'jira_issue_id' => 'required|numeric|min:0|exists:jira_issues,jira_issue_id',
            'jira_user_id' => 'required|string|exists:jira_users,jira_user_id',
            'time_spent_in_minutes' => 'required|numeric|min:0',
            'description' => 'required|string',
            'entry_created_at' => 'required|date',
            'entry_updated_at' => 'required|date',
        ];
    }

    private function rulesForPut(): array
    {
        return [
            'time_spent_in_minutes' => 'required|numeric|min:0',
            'description' => 'required|string',
        ];
    }
}
