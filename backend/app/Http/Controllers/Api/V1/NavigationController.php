<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Resources\V1\CategoryNodeResource;
use App\Http\Resources\V1\TagResource;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class NavigationController extends BaseController
{
    /**
     * Ambil menu header, tag daerah, tautan ekosistem, dan media sosial.
     */
    public function __invoke(): JsonResponse
    {
        $payload = Cache::remember('api:v1:navigation', now()->addMinutes(5), function (): array {
            $raw = [
                'header_categories' => $this->headerCategories(),
                'region_tags' => $this->regionTags(),
                'ecosystem_links' => $this->ecosystemLinks(),
                'social_links' => $this->socialLinks(),
            ];

            /** @var array<string, mixed> */
            return json_decode((string) json_encode($raw), true);
        });

        return response()->json(['data' => $payload]);
    }

    /**
     * 6 kategori induk beserta children.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    private function headerCategories()
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with(['children' => function ($query): void {
                $query->orderBy('sort_order');
            }])
            ->withCount([
                'articles as published_articles_count' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at'),
            ])
            ->orderBy('sort_order')
            ->get();

        return CategoryNodeResource::collection($categories);
    }

    /**
     * 15 tag daerah Lampung.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    private function regionTags()
    {
        $tags = Tag::query()
            ->where('type', 'region')
            ->orderBy('name')
            ->limit(15)
            ->get();

        return TagResource::collection($tags);
    }

    /**
     * Tepat 4 tautan ekosistem Lampung Geh.
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function ecosystemLinks(): array
    {
        return [
            ['label' => 'Web Lampung Geh', 'url' => 'https://lampuung.com/'],
            ['label' => 'Lowongan Lampung', 'url' => 'http://lowonganlampung.co.id/'],
            ['label' => 'Store', 'url' => 'https://shopee.co.id/lampunggehcatalog'],
            ['label' => 'Join', 'url' => 'http://lampuung.com/join'],
        ];
    }

    /**
     * Tautan media sosial.
     *
     * @return array{instagram: string, facebook: string, x: string}
     */
    private function socialLinks(): array
    {
        return [
            'instagram' => 'https://www.instagram.com/lampunggehnews/',
            'facebook' => 'https://facebook.com/lampunggeh',
            'x' => 'https://twitter.com/lampunggehh',
        ];
    }
}
