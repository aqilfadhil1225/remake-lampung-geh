<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\V1\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends BaseController
{
    /**
     * Ambil konten halaman statis berdasarkan slug.
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            return response()->json([
                'message' => 'Halaman tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => new PageResource($page),
        ]);
    }
}
