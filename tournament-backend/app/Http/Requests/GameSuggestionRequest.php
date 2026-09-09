<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'genre' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'cover_image_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
