<?php

namespace App\Repository\Eloquent;

use App\Exceptions\BadRequestException;
use App\Repository\Interfaces\RepositoryInterface;
use DateTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class BaseRepository
 *
 * @package   App\Repository\Eloquent
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function find($id, $with = []): ?Model
    {
        $query = $this->model;
        if ($with) {
            $query = $query->with($with);
        }
        return $query->find($id);
    }

    /**
     * @throws \App\Exceptions\BadRequestException
     */
    public function findOneBy($params, $with = []): ?Model
    {
        $params = array_map(function ($param) {
            if (count($param) === 2 && !is_array($param[1])) {
                return [$param[0], [$param[1]]];
            }
            if (count($param) === 3 && !is_array($param[2])) {
                return [$param[0], $param[1], [$param[2]]];
            }
            return $param;
        }, $params);
        $page = $this->findByParams($params, $with, [], ['number' => 1, 'size' => 1]);
        $items = $page->items();
        return array_shift($items);
    }

    /**
     * Find records by parameters.
     *
     * @param array $params The search parameters. Each parameter is an array with the following structure:
     *                     [key, value] or [key, option, value].
     * @param array $with The relationships to eager load.
     * @param array $order The fields to order by and their direction.
     * @param array $page The pagination parameters.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
     * @throws \App\Exceptions\BadRequestException If a parameter has an invalid structure.
     */
    public function findByParams(
        $params,
        array $with = [],
        array $order = [],
        array $page = ['number' => 1, 'size' => 30]
    ): LengthAwarePaginator|Collection {
        $query = $this->model->newQuery();
        $query = $this->proxyFilters($params, $query);
        foreach ($params as $param) {
            if (!in_array(count($param), [2, 3], true)) {
                throw new BadRequestException(
                    __('each param have to had key and value or key, option and value')
                );
            }
            $query = $this->processDirect($query, $param);
        }
        if ($with) {
            $query = $query->with($with);
        }
        foreach ($order as $field => $direction) {
            $query = $query->orderBy($field, $direction);
        }
        if (!$page) {
            return $query->get();
        }
        return $query->paginate($page['size'], ['*'], 'page', $page['number']);
    }

    protected function processDirect($query, $param): Builder|Model
    {
        //@phpstan-ignore-next-line
        return match (count($param)) {
            2 => $this->processParam($query, $param[0], $param[1]),
            3 => $this->processParam($query, $param[0], $param[2], $param[1])
        };
    }

    /**
     * Process the parameter for the query.
     *
     * @param Builder|Model $query The query builder or model instance.
     * @param string $key The key for the parameter.
     * @param array|string|null $values The value(s) for the parameter.
     * @param string $operator The comparison operator (default is '=').
     * @return Builder|Model The modified query builder or model instance.
     */
    protected function processParam(
        Builder|Model $query,
        string $key,
        array|string|null $values,
        string $operator = '='
    ): Builder|Model {
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as $value) {
            if ($operator === 'IN') {
                $query = $query->whereIn($key, $values);
            } elseif ($operator === 'NOT IN') {
                $query = $query->whereNotIn($key, $values);
            } elseif ($value instanceof DateTime) {
                $query = $query->whereDate($key, $operator, $value);
            } elseif (($value === '' || $value === null) && $operator === '=') {
                $query = $query->where(function (Builder|Model $query) use ($key, $value) {
                    $query->whereNull($key)->orWhere($key, $value);
                });
            } elseif (($value === '' || $value === null) && $operator === '!=') {
                $query = $query->where(function (Builder|Model $query) use ($key, $value) {
                    $query->whereNotNull($key)->orWhere($key, '!=', $value);
                });
            } elseif ($operator === 'like') {
                $value = $this->scapeWildcards($value);
                $query = $query->where($key, $operator, $value);
            } else {
                $query = $query->where($key, $operator, $value);
            }
        }
        return $query;
    }

    public function scapeWildcards(string $value): string
    {
        $start = str_starts_with($value, "%") ? 1 : 0;
        $cut_final = str_ends_with($value, "%") ? 1 : 0;
        $middle = substr($value, $start, strlen($value) - $start - $cut_final);
        $middle = str_replace(["\\", ",", "%", "'", "__**__"], ["__**__", '\\,', "\\%", "\\'", "%"], $middle);
        if ($start) {
            $middle = "%" . $middle;
        }
        if ($cut_final) {
            $middle .= "%";
        }
        return $middle;
    }

    protected function proxyFilters(array &$params, Builder|Model $query): Builder|Model
    {
        return $query;
    }

    protected function extractParams($relation_name, &$params): array
    {
        $relation_params = [];
        foreach ($params as $key => $param) {
            if (str_starts_with($param[0], "$relation_name.")) {
                $relation_params[] = [
                    substr($param[0], strlen("$relation_name.")),
                    $param[1],
                    $param[2],
                ];
                unset($params[$key]);
            }
        }
        return $relation_params;
    }
}
