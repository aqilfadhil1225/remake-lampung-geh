<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Article;
use Illuminate\Http\JsonResponse;

class SitemapController extends BaseController
{
    /**
     * Daftar artikel terbit < 48 jam untuk Google News Sitemap.
     */
    public function news(): JsonResponse
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $articles = Article::query()
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subHours(48))
            ->whereNotNull('published_at')
            ->with(['tags'])
            ->orderByDesc('published_at')
            ->get();

        $items = $articles->map(function (Article $article) use ($baseUrl): array {
            return [
                'loc' => $baseUrl.'/'.$article->published_at->format('Y/m/d').'/'.$article->slug.'/',
                'title' => $article->title,
                'published_at' => $article->published_at?->toIso8601String(),
                'keywords' => $article->tags->pluck('name')->all(),
            ];
        })->all();

        return response()->json([
            'data' => $items,
        ]);
    }
}
