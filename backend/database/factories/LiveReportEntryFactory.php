<?php

namespace Database\Factories;

use App\Models\LiveReportEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveReportEntry>
 */
class LiveReportEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'title' => fake()->sentence(8, true),
            'content' => '<p>'.fake()->paragraphs(2, true).'</p>',
            'image_path' => null,
            'published_at' => now(),
        ];
    }
}
