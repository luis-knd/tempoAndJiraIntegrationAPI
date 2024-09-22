<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Jira\JiraIssue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JiraIssueResource
 *
 * @package   App\Http\Resources\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_issue_id
 * @property mixed $jira_issue_key
 * @property mixed $jira_project_id
 * @property mixed $summary
 * @property mixed $development_category
 * @property mixed $description
 * @property mixed $status
 */
class JiraIssueResource extends JsonResource
{
    use FieldsResourceTraits;

    public static function toJsonResponse(JiraIssue $jiraIssue): JsonResponse
    {
        return jsonResponse(['jira_issue' => self::make($jiraIssue)]);
    }

    public function toArray(Request $request): array
    {
        $this->init($request);
        return [
            'id' => $this->when($this->include('id'), $this->id),
            'jira_issue_id' => $this->when($this->include('jira_issue_id'), $this->jira_issue_id),
            'jira_issue_key' => $this->when($this->include('jira_issue_key'), $this->jira_issue_key),
            'jira_project_id' => $this->when($this->include('jira_project_id'), $this->jira_project_id),
            'summary' => $this->when($this->include('summary'), $this->summary),
            'development_category' => $this->when($this->include('development_category'), $this->development_category),
            'status' => $this->when($this->include('status'), $this->status)
        ];
    }
}
