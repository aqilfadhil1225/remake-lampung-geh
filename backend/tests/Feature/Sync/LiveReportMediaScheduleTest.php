<?php

namespace Tests\Feature\Sync;

use App\Http\Resources\V1\ArticleCardResource;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Policies\LiveReportEntryPolicy;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LiveReportMediaScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            CategorySeeder::class,
        ]);
    }

    public function test_live_report_assignees_gate_entry_creation_by_live_status(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('reporter');

        $otherReporter = User::factory()->create();
        $otherReporter->assignRole('reporter');

        $article = Article::factory()->liveReport('live')->create([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $article->liveReportAssignees()->attach($reporter->id);

        $this->assertDatabaseHas('live_report_assignees', [
            'article_id' => $article->id,
            'user_id' => $reporter->id,
        ]);

        $policy = new LiveReportEntryPolicy;

        $this->assertTrue($policy->create($reporter, $article));
        $this->assertFalse($policy->create($otherReporter, $article));

        $article->update(['live_status' => 'ended']);

        $this->assertFalse($policy->create($reporter, $article->fresh()));
    }

    public function test_article_reading_time_is_calculated_from_word_count(): void
    {
        $article = Article::create([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'title' => 'Artikel Uji Waktu Baca',
            'slug' => 'artikel-uji-waktu-baca-'.Str::lower(Str::random(6)),
            'excerpt' => 'Ringkasan artikel uji.',
            'content' => '<p>'.implode(' ', array_fill(0, 450, 'kata')).'</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertSame(3, $article->reading_time_minutes);
    }

    public function test_publish_scheduled_command_only_publishes_due_articles(): void
    {
        $due = Article::factory()->create([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);

        $future = Article::factory()->create([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'status' => 'scheduled',
            'published_at' => now()->addHour(),
        ]);

        $this->artisan('articles:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $due->fresh()->status);
        $this->assertSame('scheduled', $future->fresh()->status);
    }

    public function test_article_featured_image_generates_four_webp_conversions_and_resource_urls(): void
    {
        $article = Article::factory()->create([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $imagePath = storage_path('framework/testing/featured-'.Str::lower(Str::random(10)).'.png');

        if (! is_dir(dirname($imagePath))) {
            mkdir(dirname($imagePath), 0755, true);
        }

        $image = imagecreatetruecolor(1600, 900);
        $this->assertNotFalse($image);

        imagefill($image, 0, 0, imagecolorallocate($image, 30, 90, 160));
        imagepng($image, $imagePath);
        imagedestroy($image);

        try {
            $article->addMedia($imagePath)->toMediaCollection('featured_image');
        } finally {
            if (is_file($imagePath)) {
                unlink($imagePath);
            }
        }

        $media = $article->fresh()->getMedia('featured_image')->first();
        $this->assertNotNull($media);

        foreach (['thumbnail', 'card', 'detail', 'og_image'] as $conversion) {
            $path = $media->getPath($conversion);
            $this->assertFileExists($path);
            $this->assertStringEndsWith('.webp', $path);
        }

        $payload = (new ArticleCardResource($article->fresh()))->toArray(request());
        $variants = $payload['featured_image']['variants'] ?? null;

        $this->assertIsArray($variants);

        foreach (['thumbnail', 'card', 'detail', 'og_image'] as $conversion) {
            $url = $variants[$conversion] ?? null;
            $this->assertNotEmpty($url);
            $this->assertStringContainsString('.webp', $url);
            $this->assertTrue(
                filter_var($url, FILTER_VALIDATE_URL) !== false
                    || str_starts_with($url, '/'),
                "Invalid media URL for {$conversion}: {$url}",
            );
        }

        $media->delete();
    }

    private function category(): Category
    {
        return Category::where('slug', 'ekonomi')->firstOrFail();
    }
}
