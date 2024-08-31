<?php

namespace App\Repository\Eloquent\v1\Tempo;

use App\Models\v1\Tempo\TempoUser;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Tempo\TempoUserRepositoryInterface;

/**
 * Class TempoUserRepository
 *
 * @package   App\Repository\Eloquent\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TempoUserRepository extends BaseRepository implements TempoUserRepositoryInterface
{
    public function __construct(TempoUser $tempoUser)
    {
        parent::__construct($tempoUser);
    }
}
