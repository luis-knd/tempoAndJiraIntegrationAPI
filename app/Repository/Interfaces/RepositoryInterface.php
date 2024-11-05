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
    public function find(mixed $id, array $with = []): ?Model;

    public function findOneBy(array $params, array $with = []): ?Model;

    /**
     *  findByParams
     *
     * @param mixed $params
     * @param array $with
     * @param array $order
     * @param array $page
     * @return LengthAwarePaginator|Collection
     */
    public function findByParams(
        mixed $params,
        array $with = [],
        array $order = [],
        array $page = ['number' => 1, 'size' => 30]
    ): LengthAwarePaginator|Collection;
}
