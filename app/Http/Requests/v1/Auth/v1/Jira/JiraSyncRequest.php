<?php

namespace App\Http\Requests\v1\Auth\v1\Jira;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\SanitizesInput;

class JiraSyncRequest extends BaseRequest
{
    use SanitizesInput;

    protected array $fieldsToStrip = ['jql'];
    protected array $fieldsToClean = [];

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizeInput($this->all()));
    }

    protected array $publicAttributes = [
        'jql' => ['rules' => ['required', 'string']],
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     *  rules
     *
     * @return string[]
     */
    public function rules(): array
    {
        return [
            /** @example created >= startOfMonth() */
            'jql' => 'required|string'
        ];
    }
}
