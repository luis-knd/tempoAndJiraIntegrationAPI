<?php

namespace App\Http\Resources\Basic;

use App\Http\Resources\FieldsResourceTraits;
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

    public function toArray(Request $request): array
    {
        $this->init($request);
        return [
            'id' => $this->when($this->include('id'), $this->id),
            'email' => $this->when($this->include('email'), $this->email),
            'name' => $this->when($this->include('name'), $this->name),
            'lastname' => $this->when($this->include('lastname'), $this->lastname),
        ];
    }
}
