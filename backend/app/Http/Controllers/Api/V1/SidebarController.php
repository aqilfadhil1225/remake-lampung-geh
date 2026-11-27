<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\V1\ArticleCardResource;
use App\Http\Resources\V1\SidebarPayloadResource;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SidebarController extends BaseController
{
    /**
     * Ambil paket widget sidebar (recent posts, recent comments, monthly archives).
     */
    public function __invoke(): JsonResponse
    {
        $payload = Cache::remember('api:v1:sidebar', now()->addMinutes(5), function (): array {
            $resource = new SidebarPayloadResource([
                'recent_posts' => $this->recentPosts(),
                'recent_comments' => $this->recentComments(),
                'monthly_archives' => $this->monthlyArchives(),
            ]);

            /** @var array<string, mixed> */
            return json_decode((string) json_encode($resource), true);
        });

        return response()->json(['data' => $payload]);
    }

    /**
     * 5 artikel published terbaru.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Article>
     */
    private function recentPosts()
    {
        return Article::query()
            ->with(['category.parent', 'author', 'media'])
            ->withCount([
                'comments as approved_comments_count' => fn (Builder $query) => $query->where('status', 'approved'),
            ])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();
    }

    /**
     * 5 komentar approved pada artikel published terbaru.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentComments(): array
    {
        $comments = Comment::query()
            ->where('status', 'approved')
            ->whereHas('article', function (Builder $query): void {
                $query->where('status', 'published')->whereNotNull('published_at');
            })
            ->with(['article' => function ($query): void {
                $query->select('id', 'title', 'slug', 'published_at');
            }])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $comments->map(function (Comment $comment): array {
            $article = $comment->article;

            return [
                'id' => $comment->id,
                'guest_name' => $comment->guest_name,
                'excerpt' => Str::limit(strip_tags((string) $comment->content), 120),
                'article_title' => $article?->title ?? '',
                'article_url' => $article !== null
                    ? '/'.$article->published_at?->format('Y/m/d').'/'.$article->slug.'/'
                    : '',
                'created_at' => $comment->created_at?->toIso8601String(),
            ];
        })->all();
    }

    /**
     * Daftar bulan terbit artikel published urut terbaru.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthlyArchives(): array
    {
        $rows = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->selectRaw('YEAR(published_at) as year, MONTH(published_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $monthLabels = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return $rows->map(function ($row) use ($monthLabels): array {
            return [
                'year' => (int) $row->year,
                'month' => (int) $row->month,
                'label' => ($monthLabels[(int) $row->month] ?? 'Unknown').' '.$row->year,
                'url' => sprintf('/%04d/%02d/', $row->year, $row->month),
                'count' => (int) $row->count,
            ];
        })->all();
    }
}
