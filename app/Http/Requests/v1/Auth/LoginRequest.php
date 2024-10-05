<?php

namespace App\Http\Requests\v1\Auth;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\SanitizesInput;
use App\Rules\Auth\CheckPasswordRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class LoginRequest extends BaseRequest
{
    use SanitizesInput;

    protected array $fieldsToStrip = ['email'];
    protected array $fieldsToClean = ['password', 'old_password'];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return match ($this->getMethod()) {
            'POST' => $this->rulesForPost(),
            'PATCH'=> $this->rulesForPatch(),
            default => [],
        };
    }

    private function rulesForPost(): array
    {
        return [
            'email'    => 'required|string|email',
            'password' => 'required|min:8',
        ];
    }

    private function rulesForPatch(): array
    {
        return [
            'old_password' => ['required', 'min:8', new CheckPasswordRule()],
            'password' => 'required|min:8|confirmed',
        ];
    }
}
