<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\V1\ArticleCardResource;
use App\Http\Resources\V1\AuthorSummaryResource;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends BaseController
{
    /**
     * Profil penulis dan daftar artikel terbit miliknya.
     */
    public function show(string $username, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'required', 'regex:/^[1-9]\d*$/'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:50'],
        ]);

        $author = User::query()
            ->where('username', $username)
            ->where('is_active', true)
            ->first();

        if ($author === null) {
            return response()->json([
                'message' => 'Penulis tidak ditemukan.',
            ], 404);
        }

        $perPage = (int) ($validated['per_page'] ?? 12);
        $rawPage = (string) ($validated['page'] ?? '1');
        $page = strlen($rawPage) > 7 ? 1000000 : min((int) $rawPage, 1000000);

        $articles = Article::query()
            ->with(['category.parent', 'author', 'media'])
            ->withCount([
                'comments as approved_comments_count' => fn (Builder $query) => $query->where('status', 'approved'),
            ])
            ->where('author_id', $author->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => [
                'author' => new AuthorSummaryResource($author),
                'articles' => ArticleCardResource::collection($articles),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'last_page' => $articles->lastPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                ],
            ],
        ]);
    }
}
