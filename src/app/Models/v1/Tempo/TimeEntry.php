<?php

namespace App\Models\v1\Tempo;

use App\Models\v1\Jira\JiraIssue;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TimeEntry
 *
 * @package   App\Models\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $date
 * @property mixed $hours
 * @property mixed $description
 * @property mixed $issue_id
 * @property mixed $tempo_user_id
 */
class TimeEntry extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'time_entries';
    protected $fillable = [
        'date',
        'hours',
        'description',
        'issue_id',
        'tempo_user_id'
    ];

    /**
     * Define a relationship with the Jira Issue model.
     *
     * @return BelongsTo
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(JiraIssue::class);
    }

    /**
     * Define a relationship with the TempoUser model.
     *
     * @return BelongsTo
     */
    public function tempoUser(): BelongsTo
    {
        return $this->belongsTo(TempoUser::class);
    }
}
