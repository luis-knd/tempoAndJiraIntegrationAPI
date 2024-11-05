<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class FieldsRule
 *
 * @package   App\Rules
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class FieldsRule implements ValidationRule
{
    private array $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = array_keys($attributes);
    }

    /**
     * Validates the attribute based on the provided value.
     *
     * @param string  $attribute The attribute to validate.
     * @param mixed   $value     The value of the attribute.
     * @param Closure $fail      The closure to handle validation failure.
     * @return void
     *
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fields = explode(",", $value);
        foreach ($fields as $field) {
            if (!in_array($field, $this->attributes, true)) {
                $fail(__('The :field is present in :attribute param but is not available for include', [
                    'field' => $field,
                    'attribute' => $attribute,
                ]));
            }
        }
    }
}
