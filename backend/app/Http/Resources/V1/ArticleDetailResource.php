<?php

namespace App\Http\Resources\V1;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Article $resource
 */
class ArticleDetailResource extends JsonResource
{
    /**
     * Tag HTML aman yang diperbolehkan pada konten artikel.
     */
    private const ALLOWED_TAGS = '<p><br><strong><em><b><i><u><a><ul><ol><li><blockquote><h2><h3><h4><img><figure><figcaption>';

    /**
     * @param  Collection<int, Comment>|null  $comments
     * @param  Collection<int, Article>|null  $relatedArticles
     */
    public function __construct(
        $resource,
        private readonly ?Collection $comments = null,
        private readonly ?Collection $relatedArticles = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Article $article */
        $article = $this->resource;

        $card = (new ArticleCardResource($article))->toArray($request);

        return array_merge($card, [
            'content' => $this->sanitizeContent((string) $article->content),
            'views_count' => (int) $article->views_count,
            'updated_at' => $article->updated_at?->toIso8601String(),
            'editor' => $article->editor !== null
                ? ['id' => $article->editor->id, 'name' => $article->editor->name]
                : null,
            'tags' => TagResource::collection($article->tags),
            'live_report_entries' => $this->liveReportEntries($article),
            'related_articles' => ArticleCardResource::collection($this->relatedArticles ?? collect()),
            'comments' => CommentResource::collection($this->comments ?? collect()),
            'seo' => $this->seo($article),
        ]);
    }

    /**
     * Sanitasi konten HTML: hanya tag aman yang diperbolehkan.
     */
    private function sanitizeContent(string $content): string
    {
        return strip_tags($content, self::ALLOWED_TAGS);
    }

    /**
     * Entri Live Report diurutkan published_at DESC; kosong jika type='standard'.
     *
     * @return array<int, mixed>
     */
    private function liveReportEntries(Article $article): array
    {
        if ($article->type !== 'live_report') {
            return [];
        }

        $entries = $article->relationLoaded('liveReportEntries')
            ? $article->liveReportEntries
            : collect();

        return $entries
            ->sortByDesc('published_at')
            ->values()
            ->map(fn ($entry) => (new LiveReportEntryResource($entry))->toArray(request()))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function seo(Article $article): array
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $canonical = $appUrl.'/'.$article->published_at?->format('Y/m/d').'/'.$article->slug.'/';

        return [
            'meta_title' => $article->title,
            'meta_description' => $article->excerpt,
            'canonical_url' => $canonical,
            'og_image' => $this->ogImage($article),
        ];
    }

    /**
     * OG image full URI atau null.
     */
    private function ogImage(Article $article): ?string
    {
        $media = $article->getMedia('featured_image')->first();

        if ($media === null) {
            return null;
        }

        return $media->getFullUrl('og_image');
    }
}
