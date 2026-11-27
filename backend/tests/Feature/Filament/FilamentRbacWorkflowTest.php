<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ArticleResource\Pages\CreateArticle;
use App\Filament\Resources\ArticleResource\Pages\EditArticle;
use App\Filament\Resources\ArticleResource\Pages\ListArticles;
use App\Filament\Resources\ArticleResource\RelationManagers\LiveReportEntriesRelationManager;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\CommentResource\Pages\ListComments;
use App\Filament\Resources\PageResource\Pages\ListPages;
use App\Filament\Resources\TagResource\Pages\ListTags;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Widgets\EditorialOverviewWidget;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TagSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentRbacWorkflowTest extends TestCase
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
    }

    public function test_panel_access_requires_active_user_with_editorial_role(): void
    {
        $panel = Filament::getPanel('admin');

        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $editor = User::factory()->create(['is_active' => true])->assignRole('editor');
        $reporter = User::factory()->create(['is_active' => true])->assignRole('reporter');
        $inactive = User::factory()->create(['is_active' => false])->assignRole('admin');
        $noRole = User::factory()->create(['is_active' => true]);

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertTrue($editor->canAccessPanel($panel));
        $this->assertTrue($reporter->canAccessPanel($panel));
        $this->assertFalse($inactive->canAccessPanel($panel));
        $this->assertFalse($noRole->canAccessPanel($panel));
    }

    public function test_reporter_is_restricted_to_own_draft_and_in_review_articles_and_forbidden_admin_resources(): void
    {
        $reporter = User::factory()->create()->assignRole('reporter');
        $otherReporter = User::factory()->create()->assignRole('reporter');
        $category = Category::where('slug', 'ekonomi')->firstOrFail();

        $ownDraft = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $reporter->id,
            'status' => 'draft',
        ]);

        $otherArticle = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $otherReporter->id,
            'status' => 'draft',
        ]);

        $this->actingAs($reporter);

        Livewire::test(ListArticles::class)->assertSuccessful();

        // Reporter creates an article in_review; author_id forced to current reporter.
        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'Berita Ekonomi Lampung Terbaru',
                'slug' => 'berita-ekonomi-lampung-terbaru',
                'excerpt' => 'Ringkasan berita ekonomi.',
                'content' => '<p>Isi lengkap berita ekonomi Lampung.</p>',
                'status' => 'in_review',
                'category_id' => $category->id,
                'region_scope' => 'bandar_lampung',
                'type' => 'standard',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', [
            'slug' => 'berita-ekonomi-lampung-terbaru',
            'author_id' => $reporter->id,
            'status' => 'in_review',
        ]);

        // Reporter cannot inject status = 'published' on create.
        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'Percobaan Bypass Publish',
                'slug' => 'percobaan-bypass-publish',
                'excerpt' => 'Ringkasan.',
                'content' => '<p>Konten.</p>',
                'status' => 'published',
                'category_id' => $category->id,
                'region_scope' => 'bandar_lampung',
                'type' => 'standard',
            ])
            ->call('create')
            ->assertHasErrors(['data.status']);

        // Reporter cannot inject status = 'published' on edit of own article.
        Livewire::test(EditArticle::class, ['record' => $ownDraft->getRouteKey()])
            ->fillForm([
                'status' => 'published',
            ])
            ->call('save')
            ->assertHasErrors(['data.status']);

        // Reporter cannot edit another reporter's article.
        Livewire::test(EditArticle::class, ['record' => $otherArticle->getRouteKey()])
            ->assertForbidden();

        // Reporter is forbidden from Comment, Category, Tag, Page, and User resources.
        Livewire::test(ListComments::class)->assertForbidden();
        Livewire::test(ListCategories::class)->assertForbidden();
        Livewire::test(ListTags::class)->assertForbidden();
        Livewire::test(ListPages::class)->assertForbidden();
        Livewire::test(ListUsers::class)->assertForbidden();
    }

    public function test_editor_can_review_publish_and_moderate_comments_but_cannot_manage_users_categories_or_pages(): void
    {
        $editor = User::factory()->create()->assignRole('editor');
        $reporter = User::factory()->create()->assignRole('reporter');
        $category = Category::where('slug', 'pemerintahan')->firstOrFail();

        $article = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $reporter->id,
            'status' => 'in_review',
            'published_at' => null,
        ]);

        $comment = Comment::factory()->create([
            'article_id' => $article->id,
            'status' => 'pending',
        ]);

        $this->actingAs($editor);

        // Editor sends back for revision with editor_note.
        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'status' => 'revision_needed',
                'editor_note' => 'Mohon lengkapi kutipan narasumber.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();
        $this->assertSame('revision_needed', $article->status);
        $this->assertSame($editor->id, $article->editor_id);
        $this->assertSame('Mohon lengkapi kutipan narasumber.', $article->editor_note);

        // Editor publishes article; published_at auto-filled.
        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm([
                'status' => 'published',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);

        // Editor moderates pending comment to approved via CommentResource action.
        Livewire::test(ListComments::class)
            ->assertSuccessful()
            ->callTableAction('approve', $comment);

        $this->assertSame('approved', $comment->fresh()->status);

        // Editor cannot access User, Category, or Page resources.
        Livewire::test(ListUsers::class)->assertForbidden();
        Livewire::test(ListCategories::class)->assertForbidden();
        Livewire::test(ListPages::class)->assertForbidden();
    }

    public function test_admin_can_access_all_resources_and_editorial_overview_widget(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $category = Category::where('slug', 'ekonomi')->firstOrFail();

        Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $admin->id,
            'status' => 'in_review',
        ]);

        Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $admin->id,
            'type' => 'live_report',
            'live_status' => 'live',
            'status' => 'published',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListArticles::class)->assertSuccessful();
        Livewire::test(ListComments::class)->assertSuccessful();
        Livewire::test(ListCategories::class)->assertSuccessful();
        Livewire::test(ListTags::class)->assertSuccessful();
        Livewire::test(ListPages::class)->assertSuccessful();
        Livewire::test(ListUsers::class)->assertSuccessful();

        Livewire::test(EditorialOverviewWidget::class)
            ->assertSee('Artikel In Review')
            ->assertSee('Live Report Aktif')
            ->assertSee('Komentar Pending');
    }

    public function test_live_report_entries_relation_manager_enforces_assignee_permission(): void
    {
        $assignedReporter = User::factory()->create()->assignRole('reporter');
        $unassignedReporter = User::factory()->create()->assignRole('reporter');
        $category = Category::where('slug', 'bencana-alam')->firstOrFail();

        $liveArticle = Article::factory()->create([
            'category_id' => $category->id,
            'author_id' => $assignedReporter->id,
            'type' => 'live_report',
            'live_status' => 'live',
            'status' => 'published',
        ]);
        $liveArticle->liveReportAssignees()->attach($assignedReporter->id);

        $this->actingAs($assignedReporter);

        Livewire::test(LiveReportEntriesRelationManager::class, [
            'ownerRecord' => $liveArticle,
            'pageClass' => EditArticle::class,
        ])
            ->assertSuccessful()
            ->callTableAction('create', data: [
                'title' => 'Update Lapangan Terkini',
                'content' => '<p>Situasi mulai terkendali.</p>',
                'published_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('live_report_entries', [
            'article_id' => $liveArticle->id,
            'author_id' => $assignedReporter->id,
            'title' => 'Update Lapangan Terkini',
        ]);

        $this->actingAs($unassignedReporter);

        Livewire::test(LiveReportEntriesRelationManager::class, [
            'ownerRecord' => $liveArticle,
            'pageClass' => EditArticle::class,
        ])->assertTableActionHidden('create');
    }
}
