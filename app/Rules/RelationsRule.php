<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class RelationsRule
 *
 * @package   App\Rules
 * @copyright 06-2024 Lcandesign
 * @author    Luis Candelario <lcandelario@lcandesign.com>
 */
class RelationsRule implements ValidationRule
{
    private array $relations;

    public function __construct(array $relations = [])
    {
        $this->relations = array_keys($relations);
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
        $fields = explode(",", $value);
        foreach ($fields as $field) {
            if (!in_array($field, $this->relations, true)) {
                $fail(__('The :field is present in :attribute param but is not available hydration', [
                    'field' => $field,
                    'attribute' => $attribute,
                ]));
            }
        }
    }
}
