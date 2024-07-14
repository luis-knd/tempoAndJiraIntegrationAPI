<?php

namespace App\Http\Resources\Basic;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Basic\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UserResource
 *
 * @package   App\Http\Resources
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $email
 * @property mixed $name
 * @property mixed $lastname
 */
class UserResource extends JsonResource
{
    use FieldsResourceTraits;

    /**
     * Convert a User object to a JSON response.
     *
     * @param User $user The User object to convert.
     * @return JsonResponse The JSON response containing the converted User object.
     */
    public static function toJsonResponse(User $user): JsonResponse
    {
        return jsonResponse(['user' => self::make($user)]);
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
             * @format uuid
             * @example 550e8400-e29b-41d4-a716-446655440000
             */
            'id' => $this->when($this->include('id'), $this->id),
            /**
             * @format email
             * @example lcandelario@lcandesign.com
             */
            'email' => $this->when($this->include('email'), $this->email),
            /**
             * @format name
             * @example Luis
             */
            'name' => $this->when($this->include('name'), $this->name),
            /**
             * @format lastname
             * @example Candelario
             */
            'lastname' => $this->when($this->include('lastname'), $this->lastname),
        ];
    }
}
