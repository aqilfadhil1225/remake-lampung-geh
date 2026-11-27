<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\V1\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends BaseController
{
    /**
     * Daftar tag daerah dan topik, dengan filter optional type.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'in:region,topic'],
        ]);

        $query = Tag::query()->orderBy('name');

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        return response()->json([
            'data' => TagResource::collection($query->get()),
        ]);
    }
}
