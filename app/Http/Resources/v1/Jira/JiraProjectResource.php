<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Jira\JiraProject;
use App\Models\v1\Jira\JiraProjectCategory;
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
 * @property mixed $jira_project_category_id
 * @property mixed $jiraProjectCategory
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
            'jira_project_id' => $this->when($this->include('jira_project_id'), $this->jira_project_id),
            'name' => $this->when($this->include('name'), $this->name),
            'category' => $this->when(
                $this->relationLoaded('jiraProjectCategory') && $this->depthLevel(),
                function () {
                    return JiraProjectCategoryResource::make($this->jiraProjectCategory)
                        ->setLevel($this->level + 1)
                        ->setMaxLevel($this->maxLevel)
                        ->setPossibleTransitions(false);
                },
                [
                    'jira_category_id' => $this->jira_project_category_id
                ]
            )
        ];
    }
}
