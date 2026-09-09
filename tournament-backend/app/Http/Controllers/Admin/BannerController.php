<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Banner::with('creator:id,username')->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $banner = Banner::create($data);

        return response()->json(['message' => 'Banner created.', 'banner' => $banner], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->update($this->validated($request));

        return response()->json(['message' => 'Banner updated.', 'banner' => $banner]);
    }

    public function destroy(int $id): JsonResponse
    {
        Banner::findOrFail($id)->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'link' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', Rule::in(['info', 'warning', 'success', 'danger'])],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
