<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\IncrementArticleViewJob;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class InteractionEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        RateLimiter::clear('127.0.0.1');

        $this->seed([
            RoleSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            PageSeeder::class,
        ]);
    }

    public function test_comment_submission_validates_turnstile_honeypot_nesting_and_saves_pending(): void
    {
        config([
            'services.turnstile.secret_key' => 'test-turnstile-secret',
            'services.turnstile.verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        $author = User::factory()->create();
        $category = Category::where('slug', 'ekonomi')->firstOrFail();

        $article = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $parentApproved = Comment::factory()->create([
            'article_id' => $article->id,
            'parent_id' => null,
            'status' => 'approved',
        ]);

        // 1. Valid level-1 comment returns 201 with pending status and stripped HTML.
        $res = $this->postJson("/api/v1/articles/{$article->id}/comments", [
            'guest_name' => 'Pembaca Lampung',
            'guest_email' => 'pembaca@example.test',
            'guest_website' => 'https://example.com',
            'content' => '<b>Mantap</b> beritanya <script>alert(1)</script>',
            'turnstile_token' => 'valid-token',
            'hp_field' => '',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('comments', [
            'id' => $res->json('data.id'),
            'article_id' => $article->id,
            'status' => 'pending',
            'content' => 'Mantap beritanya alert(1)',
        ]);

        // 2. Valid level-2 reply to approved top-level comment succeeds.
        $replyRes = $this->postJson("/api/v1/articles/{$article->id}/comments", [
            'parent_id' => $parentApproved->id,
            'guest_name' => 'Pembalas Satu',
            'guest_email' => 'pembalas@example.test',
            'content' => 'Setuju sekali dengan pendapat ini.',
            'turnstile_token' => 'valid-token',
        ]);

        $replyRes->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        // 3. Level-3 nested reply (replying to a level-2 comment) is rejected with 422.
        $level2 = Comment::factory()->create([
            'article_id' => $article->id,
            'parent_id' => $parentApproved->id,
            'status' => 'approved',
        ]);

        $this->postJson("/api/v1/articles/{$article->id}/comments", [
            'parent_id' => $level2->id,
            'guest_name' => 'Pembalas Tiga',
            'guest_email' => 'tiga@example.test',
            'content' => 'Balasan level tiga harus ditolak.',
            'turnstile_token' => 'valid-token',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);

        // 4. parent_id = 0 must be rejected with 422 (never hit MySQL FK 500).
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.4'])
            ->postJson("/api/v1/articles/{$article->id}/comments", [
                'parent_id' => 0,
                'guest_name' => 'Pembalas Nol',
                'guest_email' => 'nol@example.test',
                'content' => 'Parent nol harus ditolak validasi.',
                'turnstile_token' => 'valid-token',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_comment_submission_rejects_turnstile_failure_honeypot_and_missing_or_draft_article(): void
    {
        config([
            'services.turnstile.secret_key' => 'test-turnstile-secret',
            'services.turnstile.verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ]);

        $author = User::factory()->create();
        $category = Category::where('slug', 'ekonomi')->firstOrFail();

        $published = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $draft = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        // 1. Honeypot filled -> 422.
        $this->postJson("/api/v1/articles/{$published->id}/comments", [
            'guest_name' => 'Bot Spam',
            'guest_email' => 'bot@example.test',
            'content' => 'Pesan spam bot.',
            'turnstile_token' => 'valid-token',
            'hp_field' => 'bot-filled',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['hp_field']);

        // 2. Turnstile verification fails -> 422.
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false], 200),
        ]);

        $this->postJson("/api/v1/articles/{$published->id}/comments", [
            'guest_name' => 'Manusia Uji',
            'guest_email' => 'uji@example.test',
            'content' => 'Komentar valid tapi turnstile gagal.',
            'turnstile_token' => 'bad-token',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['turnstile_token']);

        // 3. Missing or draft article -> 404.
        $payload = [
            'guest_name' => 'Pembaca',
            'guest_email' => 'pembaca@example.test',
            'content' => 'Komentar pada artikel tidak ada.',
            'turnstile_token' => 'valid-token',
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.2'])
            ->postJson('/api/v1/articles/999999/comments', $payload)
            ->assertStatus(404);
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.3'])
            ->postJson("/api/v1/articles/{$draft->id}/comments", $payload)
            ->assertStatus(404);
    }

    public function test_comment_submission_enforces_rate_limit_with_retry_after(): void
    {
        config(['services.turnstile.secret_key' => null]);

        $author = User::factory()->create();
        $category = Category::where('slug', 'ekonomi')->firstOrFail();

        $article = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $payload = [
            'guest_name' => 'Penguji Rate',
            'guest_email' => 'rate@example.test',
            'content' => 'Komentar beruntun untuk uji batas.',
            'turnstile_token' => 'local-token',
        ];

        for ($i = 0; $i < 3; $i++) {
            $this->postJson("/api/v1/articles/{$article->id}/comments", $payload)
                ->assertStatus(201);
        }

        $fourth = $this->postJson("/api/v1/articles/{$article->id}/comments", $payload);
        $fourth->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_article_view_dispatches_job_and_returns_202_or_404(): void
    {
        Queue::fake();

        $author = User::factory()->create();
        $category = Category::where('slug', 'ekonomi')->firstOrFail();

        $published = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $draft = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->postJson("/api/v1/articles/{$published->id}/view")
            ->assertStatus(202)
            ->assertExactJson(['queued' => true]);

        Queue::assertPushed(IncrementArticleViewJob::class, function (IncrementArticleViewJob $job) use ($published): bool {
            return $job->articleId === $published->id;
        });

        $this->postJson('/api/v1/articles/999999/view')->assertStatus(404);
        $this->postJson("/api/v1/articles/{$draft->id}/view")->assertStatus(404);
    }

    public function test_public_get_rate_limit_can_be_bypassed_with_valid_internal_api_key(): void
    {
        config(['services.internal_api.key' => 'test-internal-api-key']);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/tags')->assertOk();
        }

        // Request #61 without X-API-Key hits 429.
        $this->getJson('/api/v1/tags')->assertStatus(429);

        // Request with valid X-API-Key bypasses the 60/min rate limit.
        $this->withHeader('X-API-Key', 'test-internal-api-key')
            ->getJson('/api/v1/tags')
            ->assertOk();
    }
}
