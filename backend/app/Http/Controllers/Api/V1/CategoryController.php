<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\V1\CategoryNodeResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    /**
     * Daftar pohon 6 kategori induk beserta children.
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with(['children' => function ($query): void {
                $query->orderBy('sort_order')
                    ->withCount([
                        'articles as published_articles_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at'),
                    ]);
            }])
            ->withCount([
                'articles as published_articles_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at'),
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => CategoryNodeResource::collection($categories),
        ]);
    }
}
