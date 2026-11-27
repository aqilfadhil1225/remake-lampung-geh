<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\IncrementArticleViewJob;
use App\Models\Article;
use Illuminate\Http\JsonResponse;

class ArticleViewController
{
    public function store(string $id): JsonResponse
    {
        if (! ctype_digit($id)) {
            return response()->json(['message' => 'Artikel tidak ditemukan.'], 404);
        }

        $article = Article::where('status', 'published')
            ->whereNotNull('published_at')
            ->find((int) $id);

        if (! $article) {
            return response()->json(['message' => 'Artikel tidak ditemukan.'], 404);
        }

        IncrementArticleViewJob::dispatch($article->id);

        return response()->json(['queued' => true], 202);
    }
}
