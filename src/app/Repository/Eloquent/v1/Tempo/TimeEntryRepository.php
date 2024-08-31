<?php

namespace App\Repository\Eloquent\v1\Tempo;

use App\Models\v1\Tempo\TimeEntry;
use App\Repository\Eloquent\BaseRepository;
use App\Repository\Interfaces\v1\Tempo\TimeEntryRepositoryInterface;

/**
 * Class TimeEntryRepository
 *
 * @package   App\Repository\Eloquent\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TimeEntryRepository extends BaseRepository implements TimeEntryRepositoryInterface
{
    public function __construct(TimeEntry $timeEntry)
    {
        parent::__construct($timeEntry);
    }
}
