<?php

namespace App\Observers\Concerns;

use App\Jobs\DispatchNextRevalidationJob;
use App\Models\Article;
use Illuminate\Support\Facades\Cache;

trait HandlesPublicRevalidation
{
    protected function flushPublicApiCaches(): void
    {
        Cache::forget('api:v1:home');
        Cache::forget('api:v1:sidebar');
        Cache::forget('api:v1:navigation');
    }

    protected function flushHomeAndSidebarCaches(): void
    {
        Cache::forget('api:v1:home');
        Cache::forget('api:v1:sidebar');
    }

    protected function dispatchNextRevalidation(string $event, array $paths = [], array $tags = []): void
    {
        DispatchNextRevalidationJob::dispatch($event, $paths, $tags);
    }

    /**
     * @return array<int, string>
     */
    protected function articleRevalidationPaths(Article $article): array
    {
        $publishedAt = $article->published_at ?? now();
        $articlePath = '/'.$publishedAt->format('Y/m/d').'/'.$article->slug.'/';
        $archivePath = '/'.$publishedAt->format('Y/m/');

        $categoryPaths = $this->categoryRevalidationPaths($article);

        return array_values(array_unique(array_merge(
            ['/', $articlePath, $archivePath],
            $categoryPaths,
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function articleRevalidationTags(Article $article): array
    {
        return ['home', 'sidebar', 'article:'.$article->id];
    }

    /**
     * @return array<int, string>
     */
    private function categoryRevalidationPaths(Article $article): array
    {
        $category = $article->category;

        if ($category === null) {
            return [];
        }

        if ($category->parent === null) {
            return ['/category/'.$category->slug.'/'];
        }

        return [
            '/category/'.$category->parent->slug.'/',
            '/category/'.$category->parent->slug.'/'.$category->slug.'/',
        ];
    }
}
