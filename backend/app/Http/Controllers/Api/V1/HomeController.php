<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\V1\ArticleCardResource;
use App\Http\Resources\V1\CategorySummaryResource;
use App\Http\Resources\V1\HomePayloadResource;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HomeController extends BaseController
{
    /**
     * Ambil seluruh paket data blok Homepage + Mega-Menu News.
     */
    public function __invoke(): HomePayloadResource
    {
        $basePublishedQuery = Article::query()
            ->with(['category.parent', 'author', 'media'])
            ->withCount([
                'comments as approved_comments_count' => fn (Builder $query) => $query->where('status', 'approved'),
            ])
            ->where('status', 'published')
            ->whereNotNull('published_at');

        $latestPosts = (clone $basePublishedQuery)
            ->orderByDesc('published_at')
            ->paginate(12);

        return new HomePayloadResource([
            'breaking_news' => $this->latestArticles(
                (clone $basePublishedQuery)->where('is_breaking', true),
                3,
            ),
            'hero_slider' => $this->latestArticles(
                (clone $basePublishedQuery)->where('is_headline', true),
                5,
            ),
            'terkini_sidebar' => $this->latestArticles(clone $basePublishedQuery, 5),
            'berita_terkini' => $this->latestArticles(clone $basePublishedQuery, 10),
            'pemerintahan' => $this->latestArticles(
                (clone $basePublishedQuery)->whereHas('category', function (Builder $query): void {
                    $query->where('slug', 'pemerintahan');
                }),
                6,
            ),
            'olahraga' => $this->latestArticles(
                (clone $basePublishedQuery)->whereHas('category', function (Builder $query): void {
                    $query->where('slug', 'olahraga');
                }),
                6,
            ),
            'recommended' => $this->latestArticles(
                (clone $basePublishedQuery)->where('is_recommended', true),
                5,
            ),
            'categories_shortcut' => Category::query()
                ->whereNull('parent_id')
                ->with(['parent', 'children.parent'])
                ->orderBy('sort_order')
                ->get(),
            'featured_news' => $this->latestArticles(
                (clone $basePublishedQuery)->where('is_featured', true),
                6,
            ),
            'featured_video' => $this->latestArticles(
                (clone $basePublishedQuery)->whereNotNull('video_url'),
                6,
            ),
            'latest_posts' => [
                'items' => $latestPosts->items(),
                'meta' => [
                    'current_page' => $latestPosts->currentPage(),
                    'last_page' => $latestPosts->lastPage(),
                    'per_page' => $latestPosts->perPage(),
                    'total' => $latestPosts->total(),
                ],
            ],
            'mega_menu_news' => $this->megaMenuNews($basePublishedQuery),
        ]);
    }

    /**
     * Ambil artikel published terbaru berdasarkan query kriteria tertentu.
     *
     * @param  Builder<Article>  $query
     */
    private function latestArticles(Builder $query, int $limit): Collection
    {
        /** @var Collection<int, Article> */
        return (clone $query)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Blok Mega-Menu News: 4 berita terbaru per sub-kategori di bawah kategori News.
     *
     * @param  Builder<Article>  $basePublishedQuery
     * @return array<int, array{category: CategorySummaryResource, articles: \Illuminate\Http\Resources\Json\AnonymousResourceCollection}>
     */
    private function megaMenuNews(Builder $basePublishedQuery): array
    {
        /** @var Category|null $newsCategory */
        $newsCategory = Category::query()
            ->whereNull('parent_id')
            ->where('slug', 'news')
            ->with('children.parent')
            ->first();

        $entries = [];

        foreach ($newsCategory?->children ?? [] as $child) {
            $articles = (clone $basePublishedQuery)
                ->where('category_id', $child->id)
                ->orderByDesc('published_at')
                ->limit(4)
                ->get();

            $entries[] = [
                'category' => new CategorySummaryResource($child),
                'articles' => ArticleCardResource::collection($articles),
            ];
        }

        return $entries;
    }
}
