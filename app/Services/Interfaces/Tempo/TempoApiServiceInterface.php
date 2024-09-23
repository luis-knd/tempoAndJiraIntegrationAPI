<?php

namespace App\Services\Interfaces\Tempo;

/**
 * Interface TempoApiServiceInterface
 *
 * @package   App\Services\Interfaces\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
interface TempoApiServiceInterface
{
    public function fetchWorklogs(int $issueId): array;
}
