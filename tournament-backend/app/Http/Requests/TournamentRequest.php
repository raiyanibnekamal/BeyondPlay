<?php
// Copyright (c) 2026 MD RAIYAN IBNE KAMAL — https://github.com/raiyanibnekamal
// SPDX-License-Identifier: LicenseRef-Proprietary

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tournamentId = $this->route('tournament');
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'game_id' => [$required, 'exists:games,id'],
            'name' => [$required, 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'prize_pool' => ['nullable', 'numeric', 'min:0'],
            'max_participants' => ['nullable', 'integer', 'min:2'],
            'format' => ['nullable', Rule::in(['single_elimination', 'double_elimination', 'round_robin'])],
            'status' => ['nullable', Rule::in(['draft', 'open', 'ongoing', 'completed', 'cancelled'])],
            'registration_start' => ['nullable', 'date'],
            'registration_end' => ['nullable', 'date'],
            'start_date' => [$required, 'date'],
            'end_date' => ['nullable', 'date'],
            'checkin_minutes_before' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
