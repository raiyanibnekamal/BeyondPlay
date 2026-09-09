<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_type' => ['required', Rule::in(['cheating', 'connection_problem', 'score_error', 'other'])],
            'description' => ['required', 'string', 'min:50'],
            'evidence_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
