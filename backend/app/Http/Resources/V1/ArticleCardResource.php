<?php

namespace App\Http\Resources\V1;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ArticleCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Article $article */
        $article = $this->resource;

        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'url' => $this->articleUrl($article),
            'type' => $article->type,
            'live_status' => $article->live_status,
            'region_scope' => $article->region_scope,
            'is_sponsored' => (bool) $article->is_sponsored,
            'sponsor_name' => $article->sponsor_name,
            'video_url' => $article->video_url,
            'reading_time_minutes' => (int) $article->reading_time_minutes,
            'approved_comments_count' => (int) ($article->approved_comments_count
                ?? $article->comments()->where('status', 'approved')->count()),
            'published_at' => $article->published_at?->toIso8601String(),
            'excerpt' => $article->excerpt,
            'featured_image' => $this->featuredImage($article),
            'category' => new CategorySummaryResource($article->category),
            'author' => new AuthorSummaryResource($article->author),
        ];
    }

    /**
     * Bangun URL artikel publik dari tanggal terbit dan slug (misal: /2026/11/26/judul-berita/).
     */
    private function articleUrl(Article $article): string
    {
        $publishedAt = $article->published_at?->format('Y/m/d') ?? '';

        return '/'.$publishedAt.'/'.$article->slug.'/';
    }

    /**
     * Bangun payload FeaturedImage dari media Spatie milik artikel.
     *
     * @return array<string, mixed>|null
     */
    private function featuredImage(Article $article): ?array
    {
        $media = $article->getMedia('featured')->first();

        if ($media === null) {
            return null;
        }

        return [
            'variants' => [
                'thumbnail' => $this->mediaUrl($media, 'thumbnail'),
                'card' => $this->mediaUrl($media, 'card'),
                'detail' => $this->mediaUrl($media, 'detail'),
                'og_image' => $this->mediaUrl($media, 'og_image'),
            ],
            'caption' => $article->image_caption,
            'credit' => $article->image_credit,
            'width' => (int) ($media->width ?? 0),
            'height' => (int) ($media->height ?? 0),
        ];
    }

    /**
     * Bangun URL media publik (full URL) untuk konversi tertentu.
     */
    private function mediaUrl(Media $media, string $conversion): string
    {
        return rtrim((string) config('app.url'), '/').$media->getUrl($conversion);
    }
}
