<?php

namespace App\Http\Requests\v1\Jira;

use App\Http\Requests\BaseRequest;

class JiraSyncRequest extends BaseRequest
{
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
            /** @example created >= startOfMonth()*/
            'jql' => 'required|string'
        ];
    }
}
