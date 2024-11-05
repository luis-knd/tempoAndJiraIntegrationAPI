<?php

namespace App\Http\Requests;

/**
 * Trait SanitizesInput
 *
 * Este trait proporciona métodos para sanitizar y limpiar entradas de formularios,
 * eliminando etiquetas HTML no deseadas y limpiando entradas específicas.
 *
 * @package   App\Http\Requests
 * @copyright 10-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
trait SanitizesInput
{
    /**
     * Get the list of fields that need to have their HTML tags stripped.
     *
     * @return array The fields that should be stripped of HTML tags.
     */
    protected function getFieldsToStrip(): array
    {
        return $this->fieldsToStrip ?? [];
    }

    /**
     * Get the list of fields that need to be cleaned.
     *
     * @return array The fields that should be cleaned.
     */
    protected function getFieldsToClean(): array
    {
        return $this->fieldsToClean ?? [];
    }

    /**
     * Sanitize the given input based on the fields to strip or clean.
     *
     * @param array $input The input data to sanitize.
     * @return array The sanitized input data.
     */
    protected function sanitizeInput(array $input): array
    {
        return collect($input)->map(function ($value, $key) {
            if (in_array($key, $this->getFieldsToStrip(), true)) {
                return $this->stripTags($value);
            }

            if (in_array($key, $this->getFieldsToClean(), true)) {
                return $this->cleanValue($value);
            }

            return $value;
        })->all();
    }

    /**
     * Recursively strips HTML and PHP tags from the given value.
     *
     * @param mixed $value The value to strip tags from. Can be an array or string.
     * @return mixed The value with tags stripped.
     */
    protected function stripTags(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'stripTags'], $value);
        }

        if (is_string($value)) {
            $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
            $value = strip_tags($value);
            $value = preg_replace('/<\?php.*?\?>/is', '', $value);
            return preg_replace('/alert\s*\$\$[^)]*\$\$/i', '', $value);
        }

        return $value;
    }


    /**
     * Recursively cleans the given value by stripping unwanted HTML content.
     *
     * @param mixed $value The value to clean. Can be an array or string.
     * @return mixed The cleaned value.
     */
    protected function cleanValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'cleanValue'], $value);
        }

        if (is_string($value) && $this->containsHtml($value)) {
            return clean($value);
        }

        return $value;
    }

    /**
     * Check if the given string contains HTML content.
     *
     * @param mixed $value The value to check. Only strings are checked.
     * @return bool True if the value contains HTML, false otherwise.
     */
    protected function containsHtml(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return $value !== strip_tags($value);
    }
}
