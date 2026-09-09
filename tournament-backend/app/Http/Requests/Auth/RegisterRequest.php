<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => is_string($this->username) ? trim($this->username) : $this->username,
            'email' => is_string($this->email) ? trim(strtolower($this->email)) : $this->email,
        ]);
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken. Choose another or log in.',
            'email.unique' => 'This email is already registered. Try logging in instead.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password_confirmation.required' => 'Please confirm your password.',
            'password_confirmation.same' => 'Password confirmation does not match.',
        ];
    }
}
