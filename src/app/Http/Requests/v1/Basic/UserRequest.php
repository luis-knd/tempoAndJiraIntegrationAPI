<?php

namespace App\Http\Requests\v1\Basic;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Auth;

class UserRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match ($this->getMethod()) {
            'GET' => Auth::check(),
            'POST' => !Auth::check(),
            'PUT' => Auth::check() && $this->userSendedIsTheAuthenticatedUser(),
            'PATCH' => $this->rulesForPatch(),
            default => false,
        };
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return match ($this->getMethod()) {
            'GET' => $this->rulesForGet(),
            'POST' => $this->rulesForPost(),
            'PUT' => $this->rulesForPut(),
            'PATCH' => $this->rulesForPatch(),
            default => [],
        };
    }

    private function rulesForGet(): array
    {
        if (!$this->route('user', false)) {
            return $this->filterRules();
        }
        return [];
    }

    private function rulesForPost(): array
    {
        return [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'name' => 'required|min:2',
            'lastname' => 'required|min:2',
        ];
    }

    private function rulesForPut(): array
    {
        return [
            'name' => 'required|min:2',
            'lastname' => 'required|min:2',
        ];
    }

    private function rulesForPatch(): array
    {
        return [
            'password' => 'required|min:8'
        ];
    }

    private function userSendedIsTheAuthenticatedUser(): bool
    {
        //@phpstan-ignore-next-line
        return Auth::id() === $this->route('user', false)->id;
    }
}
