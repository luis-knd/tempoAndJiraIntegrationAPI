<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Jira\JiraProjectCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JiraProjectCategoryResource
 *
 * @package   App\Http\Resources\v1\Jira
 * @copyright 09-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $name
 * @property mixed $description
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $projects
 * @property mixed $maxLevel
 * @property mixed $jira_category_id
 * @method relationLoaded(string $string)
 */
class JiraProjectCategoryResource extends JsonResource
{
    use FieldsResourceTraits;

    public static function toJsonResponse(JiraProjectCategory $jiraProjectCategory): JsonResponse
    {
        return jsonResponse(['jira_project_category' => self::make($jiraProjectCategory)]);
    }

    public function toArray($request): array
    {
        $this->init($request);
        return [
            'jira_category_id' => $this->when($this->include('jira_category_id'), $this->jira_category_id),
            'name' => $this->when($this->include('name'), $this->name),
            'description' => $this->when($this->include('description'), $this->description)
        ];
    }
}
