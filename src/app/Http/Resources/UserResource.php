<?php

namespace App\Http\Resources;

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
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'lastname' => $this->lastname,
        ];
    }
}
