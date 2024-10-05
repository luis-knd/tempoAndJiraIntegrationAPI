<?php

namespace App\Http\Requests\v1\Basic;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;

class UserRequest extends BaseRequest
{
    use SanitizesInput;

    protected array $fieldsToStrip = ['name', 'lastname', 'email'];
    protected array $fieldsToClean = [];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    protected array $publicAttributes = [
        'id' => ['rules' => ['uuid']],
        'name' => ['rules' => ['string', 'max:255']],
        'lastname' => ['rules' => ['string', 'max:255']],
        'email' => ['rules' => ['email']]
    ];

    protected array $relations = [];
    protected array $proxyFilters = [];

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
            'GET' => $this->rulesForGet(),
            'PUT' => $this->rulesForPut(),
            default => [],
        };
    }

    private function rulesForGet(): array
    {
        if (!$this->route('user', false)) {
            return $this->getFilterRules();
        }
        return $this->showRules();
    }

    private function rulesForPut(): array
    {
        return [
            'name' => 'required|min:2|max:255',
            'lastname' => 'required|min:2|max:255',
        ];
    }
}
