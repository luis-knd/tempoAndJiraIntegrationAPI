<?php

namespace App\Services;

/**
 * Class Criteria
 *
 * @package   App\Services
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
abstract class Criteria
{
    public const MAP = [
        'eq' => '=',
        'ne' => '!=',
        'gte' => '>=',
        'gt' => '>',
        'lte' => '<=',
        'lt' => '<',
        'in' => 'IN',
        'nin' => 'NOT IN',
        'lk' => 'like',
        'between' => 'between',
    ];
}
