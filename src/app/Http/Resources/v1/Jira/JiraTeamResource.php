<?php

namespace App\Http\Resources\v1\Jira;

use App\Models\v1\Jira\JiraTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JiraTeamResource
 *
 * @package App\Http\Resources\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_team_id
 * @property mixed $name
 */
class JiraTeamResource extends JsonResource
{
    /**
     * Convert a JiraTeam object to a JSON response.
     *
     * @param JiraTeam $team The JiraTeam object to convert.
     * @return JsonResponse The JSON response containing the converted JiraTeam object.
     */
    public static function toJsonResponse(JiraTeam $team): JsonResponse
    {
        return jsonResponse(['jira_team' => self::make($team)]);
    }

    /**
     * Convert the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jira_team_id' => $this->jira_team_id,
            'name' => $this->name,
        ];
    }
}
