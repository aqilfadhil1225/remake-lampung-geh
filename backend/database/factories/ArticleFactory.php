<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6, true);

        return [
            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'author_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'editor_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'excerpt' => fake()->paragraph(2, true),
            'content' => '<p>'.fake()->paragraphs(4, true).'</p>',
            'type' => 'standard',
            'live_status' => null,
            'region_scope' => 'bandar_lampung',
            'status' => 'published',
            'editor_note' => null,
            'is_headline' => false,
            'is_breaking' => false,
            'is_recommended' => false,
            'is_featured' => false,
            'is_sponsored' => false,
            'sponsor_name' => null,
            'featured_image_path' => null,
            'image_caption' => null,
            'image_credit' => null,
            'video_url' => null,
            'reading_time_minutes' => 3,
            'views_count' => fake()->numberBetween(0, 500),
            'published_at' => now()->subHours(rand(1, 72)),
        ];
    }

    /**
     * Artikel berstatus draft (untuk uji kebocoran data).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Artikel live report.
     */
    public function liveReport(string $liveStatus = 'live'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'live_report',
            'live_status' => $liveStatus,
        ]);
    }
}
