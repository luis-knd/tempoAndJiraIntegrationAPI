<?php

namespace App\Http\Requests;

use App\Rules\FieldsRule;
use App\Rules\RelationsRule;
use App\Rules\SortRule;
use App\Services\Criteria;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BaseRequest
 *
 * @package   App\Http\Requests
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
abstract class BaseRequest extends FormRequest
{
    protected array $proxyFilters = [];
    protected array $publicAttributes = [];
    protected array $relations = [];

    /**
     * Retrieves and processes the validated parameters.
     *
     * This function retrieves the validated parameters using the parent class's `validated` method.
     * It then iterates over the `proxyFilters` array to check if there are any proxy filters
     * that need to be mediated. If a proxy filter exists and its mediated parameter is present
     * in the validated parameters, the value of the mediated parameter is assigned to the proxy
     * filter's key and the mediated parameter is unset.
     *
     * If the 'relations' parameter is present in the validated parameters, it is processed.
     * The 'relations' parameter is exploded into an array of relations.
     * For each relation, the corresponding relation configuration is retrieved from the `relations` array.
     * If the relation configuration does not have a 'with' key, the relation is added to the `new_relations` array.
     * If the relation configuration has a 'with' key and its value is not empty, the 'with' value is added to the
     * `new_relations` array.
     * Finally, the `new_relations` array is imploded into a comma-separated string and assigned to the 'relations'
     * parameter.
     *
     * @param mixed $key The key to retrieve from the validated parameters (default: null)
     * @param mixed $default The default value to return if the key is not found (default: null)
     * @return array The processed validated parameters
     */
    public function validated($key = null, $default = null): array
    {
        $validatedParams = parent::validated();

        foreach ($this->proxyFilters as $filterKey => $filter) {
            if (isset($filter['mediate'], $validatedParams[$filter['mediate']])) {
                $validatedParams[$filterKey] = $validatedParams[$filter['mediate']];
                unset($validatedParams[$filter['mediate']]);
            }
        }

        if (isset($validatedParams['relations'])) {
            $newRelations = [];
            $relations = explode(',', $validatedParams['relations']);

            foreach ($relations as $relation) {
                $relationConfig = $this->relations[$relation] ?? [];
                if (!isset($relationConfig['with'])) {
                    $newRelations[] = $relation;
                } elseif (!empty($relationConfig['with'])) {
                    $newRelations[] = $relationConfig['with'];
                }
            }

            $validatedParams['relations'] = implode(",", $newRelations);
        }

        return $validatedParams;
    }

    /**
     * Returns an array of rules for showing fields and relations.
     *
     * @return array The rules array for showing fields and relations.
     */
    protected function showRules(): array
    {
        return [
            'fields' => ['sometimes', 'min:2', new FieldsRule($this->publicAttributes)],
            'relations' => ['sometimes', 'min:2', new RelationsRule($this->relations)],
        ];
    }

    /**
     * Returns an array of filter rules for the request based on the public attributes and proxy filters.
     *
     * @return array The filter rules array.
     */
    protected function getFilterRules(): array
    {
        $rules = [
            'page_size' => ['sometimes', 'integer', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'min:2', new SortRule($this->publicAttributes)],
            'fields' => ['sometimes', 'min:2', new FieldsRule($this->publicAttributes)],
            'relations' => ['sometimes', 'min:2', new RelationsRule($this->relations)],
        ];

        foreach ($this->publicAttributes as $attribute => $data) {
            $rules[$attribute] = $this->getAttributeRules($attribute, $data);
        }

        foreach ($this->proxyFilters as $attribute => $data) {
            $rules[$attribute] = $this->getProxyFilterRules($attribute, $data);
        }

        return $rules;
    }

    private function getAttributeRules($attribute, $data): array
    {
        if (!isset($data['rules'])) {
            return ['sometimes'];
        }

        $queryParam = $this->input($attribute);
        if (is_array($queryParam)) {
            return $this->getArrayAttributeRules($attribute, $data);
        }

        return array_merge(['sometimes', 'nullable'], $data['rules']);
    }

    private function getArrayAttributeRules($attribute, $data): array
    {
        $rules = ['sometimes', 'array'];

        foreach (Criteria::MAP as $key => $value) {
            $rules["$attribute.$key"] = array_merge(['sometimes', 'nullable'], $data['rules']);
        }

        return $rules;
    }

    private function getProxyFilterRules($attribute, $data): array
    {
        $rules = ['sometimes'];

        if (isset($data['rules'])) {
            $rules = array_merge($rules, $data['rules']);
        }

        if (isset($data['mediate'])) {
            $attribute = $data['mediate'];
        }
        $params[$attribute] = $rules;
        return $params;
    }
}
