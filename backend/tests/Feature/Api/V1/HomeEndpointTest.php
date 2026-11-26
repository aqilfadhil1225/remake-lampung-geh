<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            PageSeeder::class,
        ]);
    }

    public function test_the_home_endpoint_returns_the_complete_home_payload(): void
    {
        $response = $this->getJson('/api/v1/home');

        $response->assertOk();

        $data = $response->json('data');

        foreach ([
            'breaking_news',
            'hero_slider',
            'terkini_sidebar',
            'berita_terkini',
            'pemerintahan',
            'olahraga',
            'recommended',
            'categories_shortcut',
            'featured_news',
            'featured_video',
            'latest_posts',
            'mega_menu_news',
        ] as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        $latestPosts = $data['latest_posts'];

        $this->assertArrayHasKey('items', $latestPosts);
        $this->assertArrayHasKey('meta', $latestPosts);

        $meta = $latestPosts['meta'];

        $this->assertSame(1, $meta['current_page']);
        $this->assertSame(1, $meta['last_page']);
        $this->assertSame(12, $meta['per_page']);
        $this->assertSame(0, $meta['total']);
        $this->assertSame([], $latestPosts['items']);

        $this->assertCount(6, $data['categories_shortcut']);
        $this->assertNotEmpty($data['categories_shortcut'][0]['children']);

        $this->assertNotEmpty($data['mega_menu_news']);

        $newsSlugs = collect($data['mega_menu_news'])
            ->pluck('category.slug')
            ->all();

        $this->assertContains('ekonomi', $newsSlugs);
        $this->assertContains('foto', $newsSlugs);
        $this->assertContains('pemerintahan', $newsSlugs);
    }

    public function test_the_home_endpoint_returns_valid_article_card_structure_for_published_articles(): void
    {
        $author = User::factory()->create();

        $ekonomi = Category::where('slug', 'ekonomi')->firstOrFail();
        $pemerintahan = Category::where('slug', 'pemerintahan')->firstOrFail();
        $olahraga = Category::where('slug', 'olahraga')->firstOrFail();

        $breakingArticle = Article::create([
            'category_id' => $ekonomi->id,
            'author_id' => $author->id,
            'title' => 'Wawasan Pemerintahan Lampung',
            'slug' => 'wawasan-pemerintahan-lampung',
            'excerpt' => 'Kisah singkat pemerintahan Lampung.',
            'content' => 'Isi konten berita pemerintahan Lampung.',
            'type' => 'standard',
            'region_scope' => 'bandar_lampung',
            'status' => 'published',
            'is_headline' => true,
            'is_breaking' => true,
            'is_recommended' => false,
            'is_featured' => false,
            'is_sponsored' => false,
            'reading_time_minutes' => 5,
            'views_count' => 12,
            'published_at' => now(),
        ]);

        $featuredVideoArticle = Article::create([
            'category_id' => $pemerintahan->id,
            'author_id' => $author->id,
            'title' => 'Kebijakan Pemerintah Provinsi',
            'slug' => 'kebijakan-pemerintah-provinsi',
            'excerpt' => 'Kebijakan pemerintah provinsi.',
            'content' => 'Isi konten kebijakan pemerintah provinsi.',
            'type' => 'standard',
            'region_scope' => 'nasional',
            'status' => 'published',
            'is_headline' => false,
            'is_breaking' => false,
            'is_recommended' => true,
            'is_featured' => true,
            'is_sponsored' => true,
            'sponsor_name' => 'PT Berita Nusantara',
            'video_url' => 'https://cdn.lampunggehnews.com/videos/kebijakan-pemerintah.mp4',
            'reading_time_minutes' => 8,
            'views_count' => 40,
            'published_at' => now()->subDay(),
        ]);

        $liveReportArticle = Article::create([
            'category_id' => $olahraga->id,
            'author_id' => $author->id,
            'title' => 'Laporan Lapangan Sepak Olahraga',
            'slug' => 'laporan-lapangan-sepak-olahraga',
            'excerpt' => 'Laporan lapangan sepak olahraga.',
            'content' => 'Isi konten laporan lapangan sepak olahraga.',
            'type' => 'live_report',
            'live_status' => 'live',
            'region_scope' => 'lampung',
            'status' => 'published',
            'is_headline' => false,
            'is_breaking' => false,
            'is_recommended' => false,
            'is_featured' => true,
            'is_sponsored' => false,
            'reading_time_minutes' => 3,
            'views_count' => 7,
            'published_at' => now()->subHours(3),
        ]);

        $draftArticle = Article::create([
            'category_id' => $ekonomi->id,
            'author_id' => $author->id,
            'title' => 'Berita Draf Tidak Publik',
            'slug' => 'berita-draf-tidak-publik',
            'excerpt' => 'Konten draf yang harus disembunyikan.',
            'content' => 'Konten draf rahasia.',
            'type' => 'standard',
            'region_scope' => 'bandar_lampung',
            'status' => 'draft',
            'is_headline' => true,
            'is_breaking' => true,
            'is_recommended' => false,
            'is_featured' => false,
            'is_sponsored' => false,
            'reading_time_minutes' => 2,
            'views_count' => 0,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/home');

        $response->assertOk();

        $data = $response->json('data');

        $breakingCards = collect($data['breaking_news']);

        $this->assertCount(1, $breakingCards);

        $breakingCard = $breakingCards->first();

        $this->assertSame($breakingArticle->id, $breakingCard['id']);
        $this->assertSame($breakingArticle->title, $breakingCard['title']);
        $this->assertSame($breakingArticle->slug, $breakingCard['slug']);
        $this->assertSame($breakingArticle->type, $breakingCard['type']);
        $this->assertSame('bandar_lampung', $breakingCard['region_scope']);
        $this->assertFalse($breakingCard['is_sponsored']);
        $this->assertSame(5, $breakingCard['reading_time_minutes']);
        $this->assertSame(0, $breakingCard['approved_comments_count']);
        $this->assertNotNull($breakingCard['published_at']);
        $this->assertSame($author->username, $breakingCard['author']['username']);
        $this->assertSame('ekonomi', $breakingCard['category']['slug']);
        $this->assertSame('news', $breakingCard['category']['parent']['slug']);
        $this->assertSame(
            '/'.$breakingArticle->published_at->format('Y/m/d').'/'.$breakingArticle->slug.'/',
            $breakingCard['url'],
        );

        $featuredVideoCards = collect($data['featured_video']);

        $this->assertCount(1, $featuredVideoCards);

        $featuredVideoCard = $featuredVideoCards->first();

        $this->assertSame($featuredVideoArticle->id, $featuredVideoCard['id']);
        $this->assertSame('https://cdn.lampunggehnews.com/videos/kebijakan-pemerintah.mp4', $featuredVideoCard['video_url']);
        $this->assertTrue($featuredVideoCard['is_sponsored']);
        $this->assertSame('PT Berita Nusantara', $featuredVideoCard['sponsor_name']);
        $this->assertArrayHasKey('featured_image', $featuredVideoCard);
        $this->assertNull($featuredVideoCard['featured_image']);

        $recommendedCards = collect($data['recommended']);

        $this->assertCount(1, $recommendedCards);
        $this->assertSame($featuredVideoArticle->id, $recommendedCards->first()['id']);

        $featuredNewsCards = collect($data['featured_news']);

        $this->assertCount(2, $featuredNewsCards);
        $this->assertTrue($featuredNewsCards->contains('id', $featuredVideoArticle->id));
        $this->assertTrue($featuredNewsCards->contains('id', $liveReportArticle->id));

        $liveReportCards = collect($data['olahraga']);

        $this->assertCount(1, $liveReportCards);
        $this->assertSame($liveReportArticle->id, $liveReportCards->first()['id']);
        $this->assertSame('live_report', $liveReportCards->first()['type']);
        $this->assertSame('live', $liveReportCards->first()['live_status']);

        $pemerintahanCards = collect($data['pemerintahan']);

        $this->assertCount(1, $pemerintahanCards);
        $this->assertSame($featuredVideoArticle->id, $pemerintahanCards->first()['id']);

        $ekonomiMenu = collect($data['mega_menu_news'])
            ->firstWhere('category.slug', 'ekonomi');

        $this->assertNotNull($ekonomiMenu);
        $this->assertCount(1, $ekonomiMenu['articles']);
        $this->assertSame($breakingArticle->id, $ekonomiMenu['articles'][0]['id']);
        $this->assertSame($breakingCard['url'], $ekonomiMenu['articles'][0]['url']);
        $this->assertSame('ekonomi', $ekonomiMenu['articles'][0]['category']['slug']);
        $this->assertSame($author->username, $ekonomiMenu['articles'][0]['author']['username']);

        $serializedPayload = json_encode($data);

        $this->assertStringNotContainsString('berita-draf-tidak-publik', $serializedPayload);
    }
}
