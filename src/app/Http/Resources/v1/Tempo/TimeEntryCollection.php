<?php

namespace App\Http\Resources\v1\Tempo;

use App\Http\Resources\BaseResourceCollection;

/**
 * Class TimeEntryCollection
 *
 * @package   App\Http\Resources\v1\Tempo
 * @copyright 08-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class TimeEntryCollection extends BaseResourceCollection
{
    public static $wrap = 'time_entries';
}
