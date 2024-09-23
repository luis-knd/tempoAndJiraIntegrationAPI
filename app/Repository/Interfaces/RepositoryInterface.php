<?php

namespace App\Repository\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Interface RepositoryInterface
 *
 * @package   App\Repository\Interfaces
 * @author    lcandelario
 * @copyright 06-2024 Lcandesign
 */
interface RepositoryInterface
{
    public function find($id, $with = []): ?Model;

    public function findOneBy($params, $with = []): ?Model;

    /**
     *  findByParams
     *
     * @param $params
     * @param array $with
     * @param array $order
     * @param array $page
     * @return LengthAwarePaginator|Collection
     *
     */
    public function findByParams(
        $params,
        array $with = [],
        array $order = [],
        array $page = ['number' => 1, 'size' => 30]
    ): LengthAwarePaginator|Collection;
}
