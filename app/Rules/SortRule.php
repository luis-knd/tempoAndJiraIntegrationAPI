<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class SortRule
 *
 * @package   App\Rules
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class SortRule implements ValidationRule
{
    private array $sortFields;

    public function __construct(array $sortFields = [])
    {
        $this->sortFields = array_keys($sortFields);
    }

    /**
     * Validates the given attribute value.
     *
     * @param string $attribute The name of the attribute being validated.
     * @param mixed $value The value of the attribute being validated.
     * @param Closure $fail The closure to call when the validation fails.
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fields = array_map(function ($item) {
            return str_replace("+", "", str_replace("-", "", $item));
        }, explode(",", $value));
        foreach ($fields as $field) {
            if (!in_array($field, $this->sortFields)) {
                $fail(__('The :field is present in :attribute param but is not available for sort', [
                    'field' => $field,
                    'attribute' => $attribute,
                ]));
            }
        }
    }
}
