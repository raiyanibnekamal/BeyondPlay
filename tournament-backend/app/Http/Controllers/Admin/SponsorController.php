<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SponsorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Sponsor::orderBy('sort_order')->orderBy('name')->paginate(30));
    }

    public function store(Request $request): JsonResponse
    {
        $sponsor = Sponsor::create($this->validated($request));

        return response()->json(['message' => 'Sponsor created.', 'sponsor' => $sponsor], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $sponsor = Sponsor::findOrFail($id);
        $sponsor->update($this->validated($request));

        return response()->json(['message' => 'Sponsor updated.', 'sponsor' => $sponsor]);
    }

    public function destroy(int $id): JsonResponse
    {
        Sponsor::findOrFail($id)->delete();

        return response()->json(['message' => 'Sponsor deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'logo' => ['required', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'tier' => ['nullable', Rule::in(['title', 'gold', 'silver', 'bronze'])],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);
    }
}
