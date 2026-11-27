<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\LiveReportEntry;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArticleEndpointsTest extends TestCase
{
    use DatabaseTruncation;

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
    }

    protected function tearDown(): void
    {
        // Bersihkan row commit dari DatabaseTruncation agar test RefreshDatabase berikutnya bersih.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Comment::query()->truncate();
        LiveReportEntry::query()->truncate();
        DB::table('article_tag')->truncate();
        Article::query()->truncate();
        User::query()->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        parent::tearDown();
    }

    public function test_articles_index_supports_filters_fulltext_and_special_characters(): void
    {
        $author = User::factory()->create(['username' => 'jurnalis_lampung']);
        $ekonomi = Category::where('slug', 'ekonomi')->firstOrFail();
        $olahraga = Category::where('slug', 'olahraga')->firstOrFail();
        $bandarLampung = Tag::where('slug', 'bandar-lampung')->firstOrFail();

        $published = Article::factory()->create([
            'category_id' => $ekonomi->id,
            'author_id' => $author->id,
            'title' => 'Panen Kopi Robusta Lampung Meningkat Tajam',
            'slug' => 'panen-kopi-robusta-lampung-meningkat-tajam',
            'excerpt' => 'Produksi kopi robusta di Lampung mengalami lonjakan.',
            'content' => '<p>Petani kopi robusta Lampung mencatatkan panen raya.</p>',
            'type' => 'standard',
            'region_scope' => 'lampung',
            'status' => 'published',
            'video_url' => 'https://cdn.lampunggehnews.com/videos/kopi.mp4',
            'published_at' => now()->setDate(2026, 11, 20),
        ]);
        $published->tags()->attach($bandarLampung->id);

        Comment::factory()->create([
            'article_id' => $published->id,
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'article_id' => $published->id,
            'status' => 'pending',
        ]);

        Article::factory()->create([
            'category_id' => $olahraga->id,
            'author_id' => $author->id,
            'title' => 'Live Pertandingan Futsal Daerah',
            'slug' => 'live-pertandingan-futsal-daerah',
            'type' => 'live_report',
            'live_status' => 'live',
            'region_scope' => 'bandar_lampung',
            'status' => 'published',
            'video_url' => null,
            'published_at' => now()->setDate(2026, 10, 15),
        ]);

        Article::factory()->create([
            'category_id' => $ekonomi->id,
            'author_id' => $author->id,
            'title' => 'Artikel Draf Kopi Robusta',
            'slug' => 'artikel-draf-kopi-robusta',
            'status' => 'draft',
            'published_at' => null,
        ]);

        // 1. Unfiltered list excludes draft and includes approved_comments_count.
        $resAll = $this->getJson('/api/v1/articles');
        $resAll->assertOk()
            ->assertJsonPath('context', null)
            ->assertJsonPath('meta.total', 2);

        $firstCard = collect($resAll->json('data'))->firstWhere('id', $published->id);
        $this->assertNotNull($firstCard);
        $this->assertSame(1, $firstCard['approved_comments_count']);

        // 2. Parent category filter ('news') includes child category ('ekonomi').
        $this->getJson('/api/v1/articles?category=news')
            ->assertOk()
            ->assertJsonPath('context.type', 'category')
            ->assertJsonPath('context.label', 'Category : News')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', $published->slug);

        // 3. Tag, author, archive, region, has_video, and live_report filters.
        $this->getJson('/api/v1/articles?tag=bandar-lampung&author=jurnalis_lampung&year=2026&month=11&region=lampung&has_video=true')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/v1/articles?has_video=false&type=live_report&live_status=live')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.live_status', 'live');

        // 4. FULLTEXT search normal + boolean operator characters (must not 500).
        $this->getJson('/api/v1/articles?q=robusta')
            ->assertOk()
            ->assertJsonPath('context.type', 'search')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $published->id);

        $this->getJson('/api/v1/articles?'.http_build_query(['q' => '+ - @ <> () ~ * "']))
            ->assertOk()
            ->assertJsonPath('context.type', 'search')
            ->assertJsonPath('meta.total', 0);

        // 5. Validation errors (422).
        $this->getJson('/api/v1/articles?q=a')->assertStatus(422);
        $this->getJson('/api/v1/articles?has_video=bukan_bool')->assertStatus(422);
        $this->getJson('/api/v1/articles?per_page=100')->assertStatus(422);
    }

    public function test_article_detail_returns_sanitized_content_ordered_live_entries_and_two_level_comments(): void
    {
        $author = User::factory()->create();
        $editor = User::factory()->create(['name' => 'Editor Utama']);
        $category = Category::where('slug', 'bencana-alam')->firstOrFail();
        $tag = Tag::where('slug', 'lampung-timur')->firstOrFail();

        $publishedAt = now('UTC')->setDate(2026, 11, 26)->setTime(4, 15, 0); // 11:15 WIB

        $article = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'editor_id' => $editor->id,
            'title' => 'Live Evakuasi Banjir Lampung Timur',
            'slug' => 'live-evakuasi-banjir-lampung-timur',
            'content' => '<p>Laporan awal.</p><script>alert(1)</script>',
            'type' => 'live_report',
            'live_status' => 'live',
            'status' => 'published',
            'views_count' => 88,
            'published_at' => $publishedAt,
        ]);
        $article->tags()->attach($tag->id);

        $related = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => $publishedAt->copy()->subHour(),
        ]);

        Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $author->id,
            'slug' => 'draft-tersembunyi',
            'status' => 'draft',
            'published_at' => $publishedAt,
        ]);

        $olderEntry = LiveReportEntry::factory()->create([
            'article_id' => $article->id,
            'author_id' => $author->id,
            'title' => 'Update Lama',
            'published_at' => $publishedAt->copy()->subMinutes(30),
        ]);

        $newerEntry = LiveReportEntry::factory()->create([
            'article_id' => $article->id,
            'author_id' => $author->id,
            'title' => 'Update Terbaru',
            'published_at' => $publishedAt,
        ]);

        $parentApproved = Comment::factory()->create([
            'article_id' => $article->id,
            'parent_id' => null,
            'guest_name' => 'Pembaca Satu',
            'guest_email' => 'rahasia1@example.test',
            'ip_address' => '10.0.0.1',
            'user_agent' => 'SecretAgent',
            'status' => 'approved',
        ]);

        Comment::factory()->create([
            'article_id' => $article->id,
            'parent_id' => $parentApproved->id,
            'guest_name' => 'Pembalas Approved',
            'guest_email' => 'rahasia2@example.test',
            'status' => 'approved',
        ]);

        Comment::factory()->create([
            'article_id' => $article->id,
            'parent_id' => $parentApproved->id,
            'guest_name' => 'Pembalas Pending',
            'status' => 'pending',
        ]);

        Comment::factory()->create([
            'article_id' => $article->id,
            'parent_id' => null,
            'guest_name' => 'Komentar Spam',
            'status' => 'spam',
        ]);

        $res = $this->getJson('/api/v1/articles/2026/11/26/live-evakuasi-banjir-lampung-timur');
        $res->assertOk();

        $data = $res->json('data');

        $this->assertSame('<p>Laporan awal.</p>alert(1)', $data['content']);
        $this->assertSame(88, $data['views_count']);
        $this->assertSame($editor->id, $data['editor']['id']);
        $this->assertCount(1, $data['tags']);
        $this->assertCount(1, $data['related_articles']);
        $this->assertSame($related->id, $data['related_articles'][0]['id']);

        // Live report entries ordered published_at DESC with WIB time.
        $this->assertCount(2, $data['live_report_entries']);
        $this->assertSame($newerEntry->id, $data['live_report_entries'][0]['id']);
        $this->assertSame('11:15 WIB', $data['live_report_entries'][0]['time_wib']);
        $this->assertSame($olderEntry->id, $data['live_report_entries'][1]['id']);

        // Comments: only approved, max 2 levels, no private fields leaked.
        $this->assertCount(1, $data['comments']);
        $this->assertCount(1, $data['comments'][0]['replies']);
        $this->assertArrayNotHasKey('guest_email', $data['comments'][0]);
        $this->assertArrayNotHasKey('ip_address', $data['comments'][0]);
        $this->assertArrayNotHasKey('user_agent', $data['comments'][0]);
        $this->assertArrayNotHasKey('guest_email', $data['comments'][0]['replies'][0]);

        $serialized = json_encode($data);
        $this->assertStringNotContainsString('rahasia1@example.test', $serialized);
        $this->assertStringNotContainsString('Komentar Spam', $serialized);
        $this->assertStringNotContainsString('Pembalas Pending', $serialized);

        // SEO block.
        $this->assertSame($article->title, $data['seo']['meta_title']);
        $this->assertStringEndsWith('/2026/11/26/live-evakuasi-banjir-lampung-timur/', $data['seo']['canonical_url']);

        // 404 for draft or mismatched date; 422 for invalid date format.
        $this->getJson('/api/v1/articles/2026/11/26/draft-tersembunyi')->assertNotFound();
        $this->getJson('/api/v1/articles/2026/11/25/live-evakuasi-banjir-lampung-timur')->assertNotFound();
        $this->getJson('/api/v1/articles/abcd/99/99/live-evakuasi-banjir-lampung-timur')->assertStatus(422);
    }
}
