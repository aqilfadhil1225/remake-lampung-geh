<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\Api\V1\ListArticlesRequest;
use App\Http\Resources\V1\ArticleCardResource;
use App\Http\Resources\V1\ArticleDetailResource;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection as SupportCollection;

class ArticleController extends BaseController
{
    /**
     * Daftar artikel berpaginasi dengan filter & pencarian FULLTEXT.
     */
    public function index(ListArticlesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Article::query()
            ->with(['category.parent', 'author', 'media'])
            ->withCount([
                'comments as approved_comments_count' => fn (Builder $q) => $q->where('status', 'approved'),
            ])
            ->where('status', 'published')
            ->whereNotNull('published_at');

        $context = $this->applyFilters($query, $validated);

        $perPage = (int) ($validated['per_page'] ?? 12);
        $rawPage = (string) ($validated['page'] ?? '1');
        $page = strlen($rawPage) > 7 ? 1000000 : min((int) $rawPage, 1000000);

        $paginator = $query
            ->orderByDesc('published_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => ArticleCardResource::collection($paginator),
            'context' => $context,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Detail artikel lengkap: Live Report entries, artikel terkait, komentar approved.
     */
    public function show(string $year, string $month, string $day, string $slug): JsonResponse
    {
        $validator = validator(
            compact('year', 'month', 'day', 'slug'),
            [
                'year' => ['required', 'digits:4'],
                'month' => ['required', 'regex:/^(0[1-9]|1[0-2])$/'],
                'day' => ['required', 'regex:/^(0[1-9]|[12]\d|3[01])$/'],
                'slug' => ['required', 'string', 'min:1', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data yang dikirim tidak valid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $article = Article::query()
            ->with(['category.parent', 'author', 'editor', 'tags', 'media', 'liveReportEntries.author'])
            ->withCount([
                'comments as approved_comments_count' => fn (Builder $q) => $q->where('status', 'approved'),
            ])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereDate('published_at', "{$year}-{$month}-{$day}")
            ->where('slug', $slug)
            ->first();

        if ($article === null) {
            return response()->json([
                'message' => 'Artikel tidak ditemukan.',
            ], 404);
        }

        $approvedComments = $this->loadApprovedComments($article);

        $relatedArticles = $this->loadRelatedArticles($article);

        return response()->json([
            'data' => new ArticleDetailResource($article, $approvedComments, $relatedArticles),
        ]);
    }

    /**
     * Terapkan seluruh filter query dan bangun objek context.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null
     */
    private function applyFilters(Builder $query, array $filters): ?array
    {
        $context = null;

        if (isset($filters['category']) && $filters['category'] !== null) {
            $context = $this->filterCategory($query, (string) $filters['category']) ?? $context;
        }

        if (isset($filters['tag']) && $filters['tag'] !== null) {
            $tagSlug = (string) $filters['tag'];
            $query->whereHas('tags', fn (Builder $q) => $q->where('slug', $tagSlug));

            if ($context === null) {
                $context = [
                    'type' => 'tag',
                    'label' => 'Tag : '.$tagSlug,
                    'description' => null,
                ];
            }
        }

        if (isset($filters['author']) && $filters['author'] !== null) {
            $username = (string) $filters['author'];
            $query->whereHas('author', fn (Builder $q) => $q->where('username', $username)->where('is_active', true));

            if ($context === null) {
                $context = [
                    'type' => 'author',
                    'label' => 'Author : '.$username,
                    'description' => null,
                ];
            }
        }

        if (isset($filters['year']) && $filters['year'] !== null) {
            $query->whereYear('published_at', (int) $filters['year']);

            if ($context === null) {
                $context = $this->archiveContext((int) $filters['year'], $filters['month'] ?? null);
            }
        }

        if (isset($filters['month']) && $filters['month'] !== null) {
            $query->whereMonth('published_at', (int) $filters['month']);
        }

        if (isset($filters['region']) && $filters['region'] !== null) {
            $query->where('region_scope', $filters['region']);
        }

        if (isset($filters['has_video']) && $filters['has_video'] !== null) {
            $this->filterHasVideo($query, (bool) $filters['has_video']);
        }

        if (isset($filters['type']) && $filters['type'] !== null) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['live_status']) && $filters['live_status'] !== null) {
            $query->where('live_status', $filters['live_status']);
        }

        if (isset($filters['q']) && $filters['q'] !== null) {
            $context = $this->filterSearch($query, (string) $filters['q']) ?? $context;
        }

        return $context;
    }

    /**
     * Filter category (parent + children). Kembalikan context jika slug valid.
     *
     * @return array<string, mixed>|null
     */
    private function filterCategory(Builder $query, string $slug): ?array
    {
        /** @var Category|null $category */
        $category = Category::query()
            ->where('slug', $slug)
            ->with('children')
            ->first();

        if ($category === null) {
            $query->whereRaw('1 = 0');

            return null;
        }

        $ids = collect([$category->id])
            ->merge($category->children->pluck('id'))
            ->all();

        $query->whereIn('category_id', $ids);

        return [
            'type' => 'category',
            'label' => 'Category : '.$category->name,
            'description' => $category->description,
        ];
    }

    /**
     * Filter has_video: true → ada video; false → tanpa video.
     */
    private function filterHasVideo(Builder $query, bool $hasVideo): void
    {
        if ($hasVideo) {
            $query->whereNotNull('video_url')->where('video_url', '!=', '');

            return;
        }

        $query->where(fn (Builder $q) => $q->whereNull('video_url')->orWhere('video_url', ''));
    }

    /**
     * Pencarian FULLTEXT MySQL InnoDB dengan sanitasi input aman.
     *
     * @return array<string, mixed>|null
     */
    private function filterSearch(Builder $query, string $q): ?array
    {
        $booleanQuery = $this->buildBooleanQuery($q);

        if ($booleanQuery === '') {
            $query->whereRaw('1 = 0');

            return [
                'type' => 'search',
                'label' => 'Search : '.$q,
                'description' => null,
            ];
        }

        $query->whereRaw('MATCH(title, excerpt, content) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);

        return [
            'type' => 'search',
            'label' => 'Search : '.$q,
            'description' => null,
        ];
    }

    /**
     * Sanitasi input q menjadi boolean query aman: hapus operator boolean,
     * pecah per kata >= 2 char, bentuk `+kata1* +kata2*`.
     */
    private function buildBooleanQuery(string $q): string
    {
        // Hapus karakter operator boolean MySQL FULLTEXT.
        $sanitized = preg_replace('/[+\-@<>()~*"]/', ' ', $q);

        if ($sanitized === null) {
            return '';
        }

        /** @var SupportCollection<int, string> $words */
        $words = collect(preg_split('/\s+/', trim($sanitized)) ?: [])
            ->filter(fn ($word) => is_string($word) && mb_strlen($word) >= 2)
            ->values();

        if ($words->isEmpty()) {
            return '';
        }

        return $words
            ->map(fn (string $word) => '+'.$this->escapeWord($word).'*')
            ->implode(' ');
    }

    /**
     * Escape karakter yang masih berbahaya pada term boolean (mis. backslash).
     */
    private function escapeWord(string $word): string
    {
        return str_replace(['\\', '"'], '', $word);
    }

    /**
     * @return array<string, mixed>
     */
    private function archiveContext(int $year, mixed $month): array
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthInt = $month !== null ? (int) $month : null;
        $label = $monthInt !== null
            ? 'Archives : '.($monthNames[$monthInt] ?? (string) $monthInt).' '.$year
            : 'Archives : '.$year;

        return [
            'type' => 'archive',
            'label' => $label,
            'description' => null,
        ];
    }

    /**
     * Muat komentar approved level-1 beserta replies (maks 2 level).
     *
     * @return EloquentCollection<int, \App\Models\Comment>
     */
    private function loadApprovedComments(Article $article): EloquentCollection
    {
        return $article->comments()
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderBy('created_at')
            ->with(['replies' => fn ($q) => $q->where('status', 'approved')->orderBy('created_at')])
            ->get();
    }

    /**
     * Muat artikel terkait (maks 6) di kategori yang sama.
     *
     * @return EloquentCollection<int, Article>
     */
    private function loadRelatedArticles(Article $article): EloquentCollection
    {
        return Article::query()
            ->with(['category.parent', 'author', 'media'])
            ->withCount([
                'comments as approved_comments_count' => fn (Builder $q) => $q->where('status', 'approved'),
            ])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();
    }
}
