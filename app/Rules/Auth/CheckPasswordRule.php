<?php

namespace App\Rules\Auth;

use App\Models\v1\Basic\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Translation\PotentiallyTranslatedString;

class CheckPasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var User $user */
        $user = Auth::user();
        Hash::check($value, $user->password) ?: $fail('The password does not match');
    }
}
