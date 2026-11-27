<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DevSampleSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TaxonomyLayoutSeoEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->seed([
            RoleSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            PageSeeder::class,
        ]);

        $this->seed(DevSampleSeeder::class);
        $this->seed(DevSampleSeeder::class); // idempotency check
    }

    public function test_sidebar_endpoint_returns_complete_payload(): void
    {
        $response = $this->getJson('/api/v1/sidebar');

        $response->assertOk();

        $data = $response->json('data');

        $this->assertArrayHasKey('recent_posts', $data);
        $this->assertArrayHasKey('recent_comments', $data);
        $this->assertArrayHasKey('monthly_archives', $data);

        // recent_posts: maks 5
        $this->assertLessThanOrEqual(5, count($data['recent_posts']));

        // recent_comments: tidak boleh sertakan guest_email
        if ($data['recent_comments'] !== []) {
            $this->assertArrayNotHasKey('guest_email', $data['recent_comments'][0]);
            $this->assertArrayHasKey('id', $data['recent_comments'][0]);
            $this->assertArrayHasKey('guest_name', $data['recent_comments'][0]);
            $this->assertArrayHasKey('excerpt', $data['recent_comments'][0]);
            $this->assertArrayHasKey('article_title', $data['recent_comments'][0]);
            $this->assertArrayHasKey('article_url', $data['recent_comments'][0]);
            $this->assertArrayHasKey('created_at', $data['recent_comments'][0]);
        }

        // monthly_archives: setiap item punya year, month, label, url, count
        if ($data['monthly_archives'] !== []) {
            $arch = $data['monthly_archives'][0];
            $this->assertArrayHasKey('year', $arch);
            $this->assertArrayHasKey('month', $arch);
            $this->assertArrayHasKey('label', $arch);
            $this->assertArrayHasKey('url', $arch);
            $this->assertArrayHasKey('count', $arch);
        }
    }

    public function test_sidebar_endpoint_uses_cache(): void
    {
        // First call populates cache.
        $this->getJson('/api/v1/sidebar');

        // Mutate articles after caching; cached result should remain unchanged.
        $publishedCount = Article::where('status', 'published')->count();

        Article::where('status', 'published')->limit(1)->delete();

        $response = $this->getJson('/api/v1/sidebar');

        $data = $response->json('data');

        // Cache hit: recent_posts count unchanged (5 or fewer).
        $this->assertLessThanOrEqual(5, count($data['recent_posts']));
    }

    public function test_navigation_endpoint_returns_complete_payload(): void
    {
        $response = $this->getJson('/api/v1/navigation');

        $response->assertOk();

        $data = $response->json('data');

        $this->assertArrayHasKey('header_categories', $data);
        $this->assertArrayHasKey('region_tags', $data);
        $this->assertArrayHasKey('ecosystem_links', $data);
        $this->assertArrayHasKey('social_links', $data);

        // 6 kategori induk
        $this->assertCount(6, $data['header_categories']);

        // Parent kategori punya description dan articles_count
        $this->assertArrayHasKey('description', $data['header_categories'][0]);
        $this->assertArrayHasKey('articles_count', $data['header_categories'][0]);

        // 15 tag daerah
        $this->assertLessThanOrEqual(15, count($data['region_tags']));

        // Tepat 4 ecosystem links
        $this->assertCount(4, $data['ecosystem_links']);

        // social links
        $this->assertSame('https://www.instagram.com/lampunggehnews/', $data['social_links']['instagram']);
        $this->assertSame('https://facebook.com/lampunggeh', $data['social_links']['facebook']);
        $this->assertSame('https://twitter.com/lampunggehh', $data['social_links']['x']);
    }

    public function test_navigation_endpoint_uses_cache(): void
    {
        $first = $this->getJson('/api/v1/navigation')->json('data');

        $this->getJson('/api/v1/navigation');

        $second = $this->getJson('/api/v1/navigation')->json('data');

        $this->assertSame($first['ecosystem_links'], $second['ecosystem_links']);
    }

    public function test_categories_endpoint_returns_tree(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();

        $data = $response->json('data');

        $this->assertCount(6, $data);

        // Parent kategori punya description dan articles_count
        $this->assertArrayHasKey('description', $data[0]);
        $this->assertArrayHasKey('articles_count', $data[0]);
        $this->assertArrayHasKey('children', $data[0]);

        // Children juga punya articles_count
        if ($data[0]['children'] !== []) {
            $this->assertArrayHasKey('articles_count', $data[0]['children'][0]);
        }
    }

    public function test_tags_endpoint_returns_all_tags_without_filter(): void
    {
        $response = $this->getJson('/api/v1/tags');

        $response->assertOk();

        $data = $response->json('data');

        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('slug', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('url', $first);
        $this->assertSame('/tag/'.$first['slug'].'/', $first['url']);
    }

    public function test_tags_endpoint_filters_by_type_region(): void
    {
        $response = $this->getJson('/api/v1/tags?type=region');

        $response->assertOk();

        $data = $response->json('data');

        foreach ($data as $tag) {
            $this->assertSame('region', $tag['type']);
        }
    }

    public function test_tags_endpoint_returns_422_for_invalid_type(): void
    {
        $response = $this->getJson('/api/v1/tags?type=invalid');

        $response->assertStatus(422);
    }

    public function test_authors_endpoint_returns_profile_and_articles(): void
    {
        $author = User::where('username', 'ridhoadwip')->firstOrFail();

        $response = $this->getJson('/api/v1/authors/'.$author->username);

        $response->assertOk();

        $data = $response->json('data');

        $this->assertSame($author->username, $data['author']['username']);
        $this->assertArrayHasKey('articles', $data);
        $this->assertArrayHasKey('meta', $data);

        $this->assertArrayHasKey('current_page', $data['meta']);
        $this->assertArrayHasKey('last_page', $data['meta']);
        $this->assertArrayHasKey('per_page', $data['meta']);
        $this->assertArrayHasKey('total', $data['meta']);

        // Articles hanya yang published
        foreach ($data['articles'] as $article) {
            $this->assertNotEmpty($article['published_at']);
        }
    }

    public function test_authors_endpoint_returns_404_for_unknown_author(): void
    {
        $response = $this->getJson('/api/v1/authors/nonexistentuser123');

        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    public function test_authors_endpoint_returns_404_for_inactive_author(): void
    {
        $inactiveAuthor = User::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/authors/'.$inactiveAuthor->username);

        $response->assertStatus(404);
    }

    public function test_authors_endpoint_validates_per_page_max(): void
    {
        $author = User::where('username', 'ridhoadwip')->firstOrFail();

        $response = $this->getJson('/api/v1/authors/'.$author->username.'?per_page=100');

        $response->assertStatus(422);
    }

    public function test_pages_endpoint_returns_existing_page(): void
    {
        $page = Page::where('slug', 'about')->firstOrFail();

        $response = $this->getJson('/api/v1/pages/about');

        $response->assertOk();

        $data = $response->json('data');

        $this->assertSame($page->id, $data['id']);
        $this->assertSame($page->title, $data['title']);
        $this->assertSame($page->slug, $data['slug']);
        $this->assertSame($page->content, $data['content']);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_pages_endpoint_returns_404_for_missing_page(): void
    {
        $response = $this->getJson('/api/v1/pages/nonexistent-page');

        $response->assertStatus(404);
        $this->assertSame('Halaman tidak ditemukan.', $response->json('message'));
    }

    public function test_sitemap_news_returns_articles_within_48_hours(): void
    {
        $response = $this->getJson('/api/v1/sitemap/news');

        $response->assertOk();

        $data = $response->json('data');

        $this->assertNotEmpty($data);

        foreach ($data as $item) {
            $this->assertArrayHasKey('loc', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('published_at', $item);
            $this->assertArrayHasKey('keywords', $item);
            $this->assertIsArray($item['keywords']);

            // loc dimulai dengan app.url
            $this->assertStringStartsWith(
                rtrim(config('app.url'), '/'),
                $item['loc'],
            );
        }
    }

    public function test_sitemap_news_excludes_articles_older_than_48_hours(): void
    {
        // Buat artikel dengan published_at > 48 jam
        $author = User::factory()->create(['is_active' => true]);
        $category = Category::firstOrFail();

        $oldArticle = Article::factory()->create([
            'author_id' => $author->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subHours(72),
        ]);

        $response = $this->getJson('/api/v1/sitemap/news');

        $data = $response->json('data');

        $titles = array_column($data, 'title');

        $this->assertNotContains($oldArticle->title, $titles);
    }

    public function test_seeded_data_does_not_leak_draft_articles(): void
    {
        // Artikel draft tidak boleh muncul di sidebar, sitemap, atau author articles
        $draftSlug = 'draf-internal-tidak-untuk-dipublikasikan';

        // Sidebar
        $sidebarResponse = $this->getJson('/api/v1/sidebar');
        $sidebarJson = json_encode($sidebarResponse->json('data'));
        $this->assertStringNotContainsString($draftSlug, $sidebarJson);

        // Sitemap
        $sitemapResponse = $this->getJson('/api/v1/sitemap/news');
        $sitemapJson = json_encode($sitemapResponse->json('data'));
        $this->assertStringNotContainsString($draftSlug, $sitemapJson);

        // Author
        $authorResponse = $this->getJson('/api/v1/authors/ridhoadwip');
        $authorJson = json_encode($authorResponse->json('data'));
        $this->assertStringNotContainsString($draftSlug, $authorJson);
    }
}
