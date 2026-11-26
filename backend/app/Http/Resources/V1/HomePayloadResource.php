<?php

namespace App\Http\Resources\V1;

use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomePayloadResource extends JsonResource
{
    /**
     * Payload agregasi blok Homepage + Mega-Menu News.
     *
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        /** @var Collection<int, Article> $latestItems */
        $latestItems = $data['latest_posts']['items'];

        return [
            'breaking_news' => ArticleCardResource::collection($data['breaking_news']),
            'hero_slider' => ArticleCardResource::collection($data['hero_slider']),
            'terkini_sidebar' => ArticleCardResource::collection($data['terkini_sidebar']),
            'berita_terkini' => ArticleCardResource::collection($data['berita_terkini']),
            'pemerintahan' => ArticleCardResource::collection($data['pemerintahan']),
            'olahraga' => ArticleCardResource::collection($data['olahraga']),
            'recommended' => ArticleCardResource::collection($data['recommended']),
            'categories_shortcut' => CategoryNodeResource::collection($data['categories_shortcut']),
            'featured_news' => ArticleCardResource::collection($data['featured_news']),
            'featured_video' => ArticleCardResource::collection($data['featured_video']),
            'latest_posts' => [
                'items' => ArticleCardResource::collection($latestItems),
                'meta' => $data['latest_posts']['meta'],
            ],
            'mega_menu_news' => $data['mega_menu_news'],
        ];
    }
}
