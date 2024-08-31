<?php

namespace App\Http\Resources\v1\Tempo;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Tempo\TempoUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TempoUserResource
 *
 * @package   App\Http\Resources\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $tempo_user_id
 * @property mixed $name
 * @property mixed $email
 */
class TempoUserResource extends JsonResource
{
    use FieldsResourceTraits;

    public static function toJsonResponse(TempoUser $tempoUser): JsonResponse
    {
        return jsonResponse(['tempo_user' => self::make($tempoUser)]);
    }

    public function toArray(Request $request): array
    {
        $this->init($request);
        return [
            'id' => $this->when($this->include('id'), $this->id),
            'tempo_user_id' => $this->when($this->include('tempo_user_id'), $this->tempo_user_id),
            'name' => $this->when($this->include('name'), $this->name),
            'email' => $this->when($this->include('email'), $this->email),
        ];
    }
}
