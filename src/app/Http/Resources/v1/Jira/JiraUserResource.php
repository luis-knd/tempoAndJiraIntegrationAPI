<?php

namespace App\Http\Resources\v1\Jira;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Jira\JiraUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class JiraUserResource
 *
 * @package   App\Http\Resources\v1\Jira
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $jira_user_id
 * @property mixed $name
 * @property mixed $email
 * @property mixed $created_at
 * @property mixed $updated_at
 */
class JiraUserResource extends JsonResource
{
    use FieldsResourceTraits;

    /**
     * Convert a JiraUser object to a JSON response.
     *
     * @param JiraUser $jiraUser The JiraUser object to convert.
     * @return JsonResponse The JSON response containing the converted JiraUser object.
     */
    public static function toJsonResponse(JiraUser $jiraUser): JsonResponse
    {
        return jsonResponse(['jira_user' => self::make($jiraUser)]);
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
        return [
            /**
             * @format  uuid
             * @example 550e8400-e29b-41d4-a716-446655440000
             */
            'id' => $this->when($this->include('id'), $this->id),
            /**
             * @format  string
             * @example abc123def456
             */
            'jira_user_id' => $this->when($this->include('jira_user_id'), $this->jira_user_id),
            /**
             * @format  name
             * @example Luis Candelario
             */
            'name' => $this->when($this->include('name'), $this->name),
            /**
             * @format  email
             * @example lcandelario@lcandesign.com
             */
            'email' => $this->when($this->include('email'), $this->email),
            /**
             * @format  datetime
             * @example 2024-08-24 12:34:56
             */
            'created_at' => $this->when($this->include('created_at'), $this->created_at),
            'updated_at' => $this->when($this->include('updated_at'), $this->updated_at),
        ];
    }
}
