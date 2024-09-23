<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Jira\JiraProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JiraProjectResource
 *
 * @package   App\Http\Resources\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_project_id
 * @property mixed $name
 * @property mixed $max_level
 * @method relationLoaded(string $string)
 */
class JiraProjectResource extends JsonResource
{
    use FieldsResourceTraits;

    public static function toJsonResponse(JiraProject $jiraProject): JsonResponse
    {
        return jsonResponse(['jira_project' => self::make($jiraProject)]);
    }

    public function toArray(Request $request): array
    {
        $this->init($request);
        return [
            'id' => $this->when($this->include('id'), $this->id),
            'jira_project_id' => $this->when($this->include('jira_project_id'), $this->jira_project_id),
            'jira_projects_category' => $this->when(
                $this->relationLoaded('jira_projects_categories') && $this->depthLevel(),
                function () {
                    return JiraProjectCategoryResource::collection($this->jira_project_id) // @phpstan-ignore-line
                        ->setLevel($this->level + 1)
                        ->setMaxLevel($this->max_level);
                }
            ),
            'name' => $this->when($this->include('name'), $this->name),
        ];
    }
}
