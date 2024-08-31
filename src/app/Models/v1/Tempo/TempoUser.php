<?php

namespace App\Models\v1\Tempo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TempoUser
 *
 * @package   App\Models\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @property mixed $tempo_user_id
 * @property mixed $name
 * @property mixed $email
 */
class TempoUser extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'tempo_users';
    protected $fillable = ['tempo_user_id', 'name', 'email'];
}
