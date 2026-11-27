<?php

namespace Tests\Feature\Sync;

use App\Jobs\DispatchNextRevalidationJob;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\LiveReportEntry;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ObserversAndRevalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CategorySeeder::class);
    }

    public function test_public_article_and_approved_comment_flush_public_api_caches(): void
    {
        Queue::fake();

        $this->putPublicApiCaches();

        $article = $this->publishedArticle();

        $this->assertPublicApiCachesMissing();

        $this->putPublicApiCaches();

        Comment::factory()->create([
            'article_id' => $article->id,
            'status' => 'approved',
        ]);

        $this->assertFalse(Cache::has('api:v1:home'));
        $this->assertFalse(Cache::has('api:v1:sidebar'));
        $this->assertSame('cached', Cache::get('api:v1:navigation'));
    }

    public function test_draft_article_and_pending_comment_do_not_dispatch_revalidation(): void
    {
        Queue::fake();

        $article = $this->draftArticle();
        $article->update(['title' => 'Draft Masih Draf']);

        Comment::factory()->create([
            'article_id' => $article->id,
            'status' => 'pending',
        ]);

        Queue::assertNotPushed(DispatchNextRevalidationJob::class);
    }

    public function test_article_publication_dispatches_article_published_with_contract_payload(): void
    {
        Queue::fake();

        $article = $this->draftArticle();
        $article->update([
            'status' => 'published',
            'published_at' => '2026-11-26 12:00:00',
        ]);

        $articlePath = '/2026/11/26/'.$article->slug.'/';

        Queue::assertPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job) use ($article, $articlePath): bool {
            return $job->event === 'article.published'
                && in_array('/', $job->paths, true)
                && in_array('/category/news/', $job->paths, true)
                && in_array('/category/news/ekonomi/', $job->paths, true)
                && in_array($articlePath, $job->paths, true)
                && in_array('/2026/11/', $job->paths, true)
                && $job->tags === ['home', 'sidebar', 'article:'.$article->id];
        });
    }

    public function test_published_article_update_dispatches_article_updated(): void
    {
        $article = $this->publishedArticle();

        Queue::fake();

        $article->update(['title' => 'Judul Artikel Diperbarui']);

        Queue::assertPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job) use ($article): bool {
            return $job->event === 'article.updated'
                && in_array('article:'.$article->id, $job->tags, true)
                && in_array('/2026/11/26/'.$article->slug.'/', $job->paths, true);
        });
    }

    public function test_archived_and_deleted_published_articles_dispatch_article_archived(): void
    {
        $archived = $this->publishedArticle();
        $deleted = $this->publishedArticle();

        Queue::fake();

        $archived->update(['status' => 'archived']);
        $deleted->delete();

        Queue::assertPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job) use ($archived): bool {
            return $job->event === 'article.archived'
                && in_array('article:'.$archived->id, $job->tags, true);
        });

        Queue::assertPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job) use ($deleted): bool {
            return $job->event === 'article.archived'
                && in_array('article:'.$deleted->id, $job->tags, true);
        });
    }

    public function test_live_report_entry_dispatches_only_when_parent_article_is_published(): void
    {
        $published = Article::factory()->liveReport('live')->create([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'status' => 'published',
            'published_at' => now()->setDate(2026, 11, 26)->setTime(12, 0, 0),
        ]);

        $draft = Article::factory()->draft()->create([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'type' => 'live_report',
            'live_status' => 'live',
        ]);

        Queue::fake();

        LiveReportEntry::factory()->create([
            'article_id' => $published->id,
            'author_id' => User::factory()->create()->id,
        ]);

        LiveReportEntry::factory()->create([
            'article_id' => $draft->id,
            'author_id' => User::factory()->create()->id,
        ]);

        Queue::assertPushedTimes(DispatchNextRevalidationJob::class, 1);

        Queue::assertPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job) use ($published): bool {
            return $job->event === 'live_report.entry_added'
                && $job->tags === ['home', 'sidebar', 'article:'.$published->id]
                && in_array('/2026/11/26/'.$published->slug.'/', $job->paths, true);
        });

        Queue::assertNotPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job) use ($draft): bool {
            return in_array('article:'.$draft->id, $job->tags, true);
        });
    }

    public function test_approved_comment_dispatches_comment_approved_payload(): void
    {
        $article = $this->publishedArticle();

        Queue::fake();

        Comment::factory()->create([
            'article_id' => $article->id,
            'status' => 'pending',
        ]);

        Comment::factory()->create([
            'article_id' => $article->id,
            'status' => 'approved',
        ]);

        Queue::assertPushedTimes(DispatchNextRevalidationJob::class, 1);

        Queue::assertPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job) use ($article): bool {
            return $job->event === 'comment.approved'
                && $job->tags === ['sidebar', 'article:'.$article->id]
                && in_array('/2026/11/26/'.$article->slug.'/', $job->paths, true);
        });
    }

    public function test_category_and_tag_changes_flush_caches_without_dispatching_revalidation(): void
    {
        Queue::fake();

        $category = Category::where('slug', 'ekonomi')->firstOrFail();

        $this->putPublicApiCaches();
        $category->update(['name' => 'Ekonomi Diperbarui']);
        $this->assertPublicApiCachesMissing();

        $this->putPublicApiCaches();

        $tag = Tag::create([
            'name' => 'Tag Uji Observer',
            'slug' => 'tag-uji-observer-'.Str::lower(Str::random(6)),
            'type' => 'topic',
        ]);

        $this->assertPublicApiCachesMissing();

        $this->putPublicApiCaches();
        $tag->delete();
        $this->assertPublicApiCachesMissing();

        Queue::assertNotPushed(DispatchNextRevalidationJob::class);
    }

    public function test_page_save_dispatches_page_updated(): void
    {
        Queue::fake();

        Page::create([
            'title' => 'Tentang Uji',
            'slug' => 'tentang-uji',
            'content' => '<p>Konten halaman uji.</p>',
        ]);

        Queue::assertPushedTimes(DispatchNextRevalidationJob::class, 1);

        Queue::assertPushed(DispatchNextRevalidationJob::class, function (DispatchNextRevalidationJob $job): bool {
            return $job->event === 'page.updated'
                && $job->paths === ['/tentang-uji/']
                && $job->tags === ['page:tentang-uji'];
        });
    }

    public function test_dispatch_next_revalidation_job_sends_contract_payload_with_secret_header(): void
    {
        config([
            'services.next_revalidate.enabled' => true,
            'services.next_revalidate.url' => 'https://next.test/api/revalidate',
            'services.next_revalidate.secret' => 'test-revalidate-secret',
            'services.next_revalidate.timeout' => 3,
        ]);

        Http::fake([
            'https://next.test/api/revalidate' => Http::response(['revalidated' => true], 200),
        ]);

        $job = new DispatchNextRevalidationJob(
            'article.published',
            ['/', '/category/news/', '/', '/tag/bandar-lampung/'],
            ['home', 'article:1', 'home'],
        );

        $job->handle();

        Http::assertSent(function (ClientRequest $request): bool {
            $data = $request->data();

            return $request->hasHeader('X-Revalidate-Secret', 'test-revalidate-secret')
                && $data['event'] === 'article.published'
                && $data['paths'] === ['/', '/category/news/', '/tag/bandar-lampung/']
                && $data['tags'] === ['home', 'article:1'];
        });
    }

    public function test_dispatch_next_revalidation_job_noops_when_disabled_or_secret_missing(): void
    {
        Http::fake();

        config([
            'services.next_revalidate.enabled' => false,
            'services.next_revalidate.url' => 'https://next.test/api/revalidate',
            'services.next_revalidate.secret' => 'test-revalidate-secret',
        ]);

        (new DispatchNextRevalidationJob('article.updated'))->handle();

        config([
            'services.next_revalidate.enabled' => true,
            'services.next_revalidate.secret' => null,
        ]);

        (new DispatchNextRevalidationJob('article.updated'))->handle();

        config(['services.next_revalidate.secret' => '']);

        (new DispatchNextRevalidationJob('article.updated'))->handle();

        Http::assertNothingSent();
    }

    private function publishedArticle(array $overrides = []): Article
    {
        return Article::factory()->create(array_merge([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
            'status' => 'published',
            'published_at' => now()->setDate(2026, 11, 26)->setTime(12, 0, 0),
        ], $overrides));
    }

    private function draftArticle(array $overrides = []): Article
    {
        return Article::factory()->draft()->create(array_merge([
            'category_id' => $this->category()->id,
            'author_id' => User::factory()->create()->id,
        ], $overrides));
    }

    private function category(): Category
    {
        return Category::where('slug', 'ekonomi')->firstOrFail();
    }

    private function putPublicApiCaches(): void
    {
        Cache::put('api:v1:home', 'cached', 60);
        Cache::put('api:v1:sidebar', 'cached', 60);
        Cache::put('api:v1:navigation', 'cached', 60);
    }

    private function assertPublicApiCachesMissing(): void
    {
        $this->assertFalse(Cache::has('api:v1:home'));
        $this->assertFalse(Cache::has('api:v1:sidebar'));
        $this->assertFalse(Cache::has('api:v1:navigation'));
    }
}
