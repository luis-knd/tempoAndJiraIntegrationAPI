<?php

namespace App\Services;

use App\Exceptions\UnprocessableException;
use DateTime;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait ProcessParamsTraits
 *
 * @package   App\Services
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
trait ProcessParamsTraits
{
    /**
     * Process the given parameters and return an array with the processed values.
     *
     * @param array $params The parameters to process.
     * @return array The processed values.
     * @throws UnprocessableException If the parameters cannot be processed.
     * @throws JsonException
     */
    protected function process(array $params): array
    {
        $fields = $this->produceFields($params);
        $with = $this->extractWith($params);
        $order = $this->produceOrder($params);
        $page = $this->producePage($params);
        $filter = $this->produceFilter($params);

        return [
            'filter' => $filter,
            'page' => $page,
            'fields' => $fields,
            'order' => $order,
            'with' => $with,
        ];
    }

    /**
     * Generates an array of selected fields based on the provided query parameters.
     *
     * @param array &$queryParams The query parameters to process.
     * @return array The array of selected fields.
     */
    protected function produceFields(array &$queryParams): array
    {
        $selectedFields = ['*'];

        if (isset($queryParams['fields'])) {
            $selectedFields = explode(',', $queryParams['fields']);
            unset($queryParams['fields']);
        }

        return $selectedFields;
    }

    /**
     * Extracts related entities from the given parameters and returns an array of them.
     *
     * @param array &$params The parameters to extract related entities from.
     * @return array The array of related entities.
     */
    protected function extractWith(array &$params): array
    {
        $relatedEntities = [];

        if (isset($params['relations'])) {
            $relations = array_filter(explode(',', $params['relations']));
            unset($params['relations']);

            foreach ($relations as $relation) {
                $relatedEntities[] = $this->snakeToCamelCase($relation);
            }
        }

        return $relatedEntities;
    }

    /**
     * Produces an order array based on the 'sort' parameter in the given params array.
     *
     * @param array &$params The input parameters array. The 'sort' parameter will be removed from the array.
     * @return array The order array. The keys are the field names and the values are the order directions
     *                       ('asc' or 'desc').
     */
    protected function produceOrder(array &$params): array
    {
        $order = [];

        if (isset($params['sort'])) {
            $sortFields = explode(",", $params['sort']);
            unset($params['sort']);

            foreach ($sortFields as $field) {
                $orderDirection = 'asc';
                $fieldName = $field;

                if (str_starts_with($field, '-')) {
                    $orderDirection = 'desc';
                    $fieldName = substr($field, 1);
                } elseif (str_starts_with($field, '+')) {
                    $fieldName = substr($field, 1);
                }

                $order[$fieldName] = $orderDirection;
            }
        }

        return $order;
    }

    /**
     * Produces a page array based on the given parameters.
     *
     * @param array &$params The parameters to produce the page array from.
     * @return array The page array.
     */
    protected function producePage(array &$params): array
    {
        $page = ['number' => 1, 'size' => 30];

        if (array_key_exists('page_size', $params)) {
            $page['size'] = $params['page_size'];
            unset($params['page_size']);
        }
        if (array_key_exists('page', $params)) {
            $page['number'] = $params['page'];
            unset($params['page']);
        }

        return $page;
    }

    /**
     * Produces a filter array based on the given parameters.
     *
     * @param array $params The parameters to produce the filter from.
     * @return array The filter array.
     * @throws UnprocessableException If a criteria is not acceptable.
     * @throws JsonException
     */
    protected function produceFilter(array $params): array
    {
        $filter = [];

        foreach ($params as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $criteria => $val) {
                    if (!array_key_exists($criteria, Criteria::MAP)) {
                        throw new UnprocessableException(
                            __(":criteria is not an acceptable query criteria", ['criteria' => $criteria]),
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }
                    $operator = Criteria::MAP[$criteria];
                    $filter[] = $this->makeFilter($val, $field, $operator);
                }
            } else {
                $filter[] = $this->makeFilter($value, $field);
            }
        }

        return $filter;
    }

    /**
     * Generates a filter array based on the input, field, and operator.
     *
     * @param string $input    The input string to be split into values.
     * @param string $field    The field to be used in the filter.
     * @param string $operator The operator to be used in the filter. Default is '='.
     * @return array The filter array containing the field, operator, and normalized values.
     * @throws JsonException
     */
    private function makeFilter(string $input, string $field, string $operator = '='): array
    {
        $values = explode(',', $input);
        $numericValues = array_filter($values, 'is_numeric');

        if (count($values) === count($numericValues) && !in_array($field, ['code', 'name'])) {
            $values = json_decode('[' . implode(',', $numericValues) . ']', false, 512, JSON_THROW_ON_ERROR);
        }

        $normalizedValues = array_map([$this, 'normalise'], $values);

        return [$field, $operator, $normalizedValues];
    }

    /**
     * Converts a snake case string to camel case.
     *
     * @param string $snakeCaseString          The snake case string to convert.
     * @param bool   $capitalizeFirstCharacter Whether to capitalize the first character of the camel case string.
     *                                         Default is false.
     * @param string $separator                The separator used in the snake case string. Default is '_'.
     * @return string The converted camel case string.
     */
    private function snakeToCamelCase(
        string $snakeCaseString,
        bool $capitalizeFirstCharacter = false,
        string $separator = '_'
    ): string {
        $camelCaseString = str_replace($separator, '', ucwords($snakeCaseString, $separator));

        if (!$capitalizeFirstCharacter) {
            $camelCaseString = lcfirst($camelCaseString);
        }

        return $camelCaseString;
    }

    /**
     * Converts a camel case string to snake case.
     *
     * @param string $camelCaseString The camel case string to convert.
     * @param string $separator       The separator to use between words in the snake case string. Default is '_'.
     * @return string The converted snake case string.
     */
    private function camelCaseToSnake(string $camelCaseString, string $separator = '_'): string
    {
        $pattern = '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!';
        preg_match_all($pattern, $camelCaseString, $matches);
        //@phpstan-ignore-next-line
        $snakeCaseParts = array_map(function ($match) use ($separator) {
            return strtolower($match == strtoupper($match) ? $match : lcfirst($match));
        }, $matches[0]);
        return implode($separator, $snakeCaseParts);
    }

    /**
     * Validates if the given input date is in a valid format.
     *
     * @param string $date   The date to validate.
     * @param string $format The format that the date should adhere to. Default is 'Y-m-d'.
     * @return bool Returns true if the date is valid according to the format, false otherwise.
     */
    private function isDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    /**
     * Normalises the given value. If the value represents a date, it will be converted to a DateTime object.
     *
     * @param string $value The value to normalise.
     * @return string|\DateTime The normalised value.
     * @throws \Exception
     */
    private function normalise(string $value): string|DateTime
    {
        if ($this->isDate($value)) {
            return new DateTime($value);
        }
        return $value;
    }
}
