<?php

namespace App\Http\Resources\V1;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorySummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'url' => $this->categoryUrl($category),
            'parent' => $category->parent !== null
                ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                    'slug' => $category->parent->slug,
                    'url' => '/category/'.$category->parent->slug.'/',
                ]
                : null,
        ];
    }

    /**
     * Bangun URL kategori publik dari hierarki (misal: /category/news/pemerintahan/).
     */
    private function categoryUrl(Category $category): string
    {
        $parentSlug = $category->parent?->slug;

        if ($parentSlug === null) {
            return '/category/'.$category->slug.'/';
        }

        return '/category/'.$parentSlug.'/'.$category->slug.'/';
    }
}
