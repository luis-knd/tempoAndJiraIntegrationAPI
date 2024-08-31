<?php

namespace App\Http\Resources\v1\Tempo;

use App\Http\Resources\FieldsResourceTraits;
use App\Models\v1\Tempo\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TimeEntryResource
 *
 * @package   App\Http\Resources\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $id
 * @property mixed $date
 * @property mixed $hours
 * @property mixed $description
 * @property mixed $issue_id
 * @property mixed $tempo_user_id
 */
class TimeEntryResource extends JsonResource
{
    use FieldsResourceTraits;

    public static function toJsonResponse(TimeEntry $timeEntry): JsonResponse
    {
        return jsonResponse(['time_entries' => self::make($timeEntry)]);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'hours' => $this->hours,
            'description' => $this->description,
            'issue_id' => $this->issue_id,
            'tempo_user_id' => $this->tempo_user_id,
        ];
    }
}
