<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Jira\JiraTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JiraTeamResource
 *
 * @package   App\Http\Resources\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_team_id
 * @property mixed $name
 * @property mixed $jiraUsers
 * @property mixed $created_at
 * @property mixed $updated_at
 * @method relationLoaded(string $string)
 */
class JiraTeamResource extends JsonResource
{
    use FieldsResourceTraits;

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
        $this->init($request);
        $response = [
            'jira_team_id' => $this->when($this->include('jira_team_id'), $this->jira_team_id),
            'name' => $this->when($this->include('name'), $this->name),
            'created_at' => $this->when($this->include('created_at'), $this->created_at),
            'updated_at' => $this->when($this->include('updated_at'), $this->updated_at)
        ];

        if ($this->relationLoaded('jiraUsers')) {
            $response['jira_users'] = $this->jiraUsers->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });
        }

        return $response;
    }
}
