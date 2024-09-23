<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class BaseResourceCollection
 *
 * @package   App\Http\Resources
 * @copyright 07-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 *
 * @method total()
 * @method perPage()
 * @method currentPage()
 * @method lastPage()
 */
class BaseResourceCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            static::$wrap => $this->collection,
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}
