<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Comment;
use App\Models\LiveReportEntry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevSampleSeeder extends Seeder
{
    /**
     * Seed data sampel untuk lingkungan development.
     *
     * Idempoten: aman dijalankan berulang kali setelah RoleSeeder,
     * CategorySeeder, TagSeeder, dan PageSeeder.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('DevSampleSeeder dilarang dijalankan di environment production.');
        }

        $password = (string) env('DEV_SAMPLE_PASSWORD', '');

        if ($password === '') {
            throw new \RuntimeException('DEV_SAMPLE_PASSWORD belum diset. Isi variabel ini di file .env sebelum menjalankan DevSampleSeeder.');
        }

        // ------------------------------------------------------------------
        // 0. Akun dev untuk login panel (admin, editor, reporter).
        // ------------------------------------------------------------------
        $devAdmin = User::updateOrCreate(
            ['email' => 'admin@lampunggehnews.local'],
            [
                'name' => 'Dev Admin',
                'username' => 'dev_admin',
                'bio' => null,
                'avatar_path' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );
        $devAdmin->assignRole('admin');

        $devEditor = User::updateOrCreate(
            ['email' => 'editor@lampunggehnews.local'],
            [
                'name' => 'Dev Editor',
                'username' => 'dev_editor',
                'bio' => null,
                'avatar_path' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );
        $devEditor->assignRole('editor');

        $devReporter = User::updateOrCreate(
            ['email' => 'reporter@lampunggehnews.local'],
            [
                'name' => 'Dev Reporter',
                'username' => 'dev_reporter',
                'bio' => null,
                'avatar_path' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );
        $devReporter->assignRole('reporter');

        // ------------------------------------------------------------------
        // 1. Penulis aktif sampel (password acak, DILARANG hardcode).
        // ------------------------------------------------------------------
        $authorRidho = User::updateOrCreate(
            ['username' => 'ridhoadwip'],
            [
                'name' => 'Ridho Adwi P',
                'email' => 'ridho.adwi@lampunggeh.test',
                'bio' => 'Jurnalis ekonomi dan pemerintahan Lampung.',
                'avatar_path' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
            ],
        );

        $authorDea = User::updateOrCreate(
            ['username' => 'deauliahanda'],
            [
                'name' => 'Dea Auliahanda',
                'email' => 'dea.auliahanda@lampunggeh.test',
                'bio' => 'Reporter bencana alam dan kriminal Lampung.',
                'avatar_path' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
            ],
        );

        $authors = [$authorRidho, $authorDea];

        // Editor untuk beberapa artikel.
        $editor = User::updateOrCreate(
            ['username' => 'editor_sample'],
            [
                'name' => 'Editor Sample',
                'email' => 'editor.sample@lampunggeh.test',
                'bio' => 'Editor redaksi Lampung Geh.',
                'avatar_path' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
            ],
        );

        // ------------------------------------------------------------------
        // 2. Tag daerah Lampung untuk dikaitkan ke artikel.
        // ------------------------------------------------------------------
        $regionTagSlugs = [
            'bandar-lampung', 'lampung-timur', 'lampung-selatan',
            'metro', 'pringsewu', 'pesawaran',
        ];
        $regionTags = Tag::where('type', 'region')
            ->whereIn('slug', $regionTagSlugs)
            ->get()
            ->keyBy('slug');

        // ------------------------------------------------------------------
        // 3. Helper: buat atau perbarui artikel sampel berdasarkan slug.
        // ------------------------------------------------------------------
        $articlesData = $this->articlesData($authors, $editor);

        foreach ($articlesData as $data) {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $article = Article::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );

            // Kaitkan tag daerah (idempoten).
            if ($tags !== []) {
                $tagIds = Tag::where('type', 'region')
                    ->whereIn('slug', $tags)
                    ->pluck('id');
                $article->tags()->syncWithoutDetaching($tagIds);
            }
        }

        // ------------------------------------------------------------------
        // 4. Live Report entries untuk 2 artikel type=live_report.
        // ------------------------------------------------------------------
        $this->seedLiveReportEntries($authorRidho);
        $this->seedLiveReportEntries($authorDea);

        // ------------------------------------------------------------------
        // 5. Komentar approved + balasan + 1 pending.
        // ------------------------------------------------------------------
        $this->seedComments();

        // ------------------------------------------------------------------
        // 6. Pastikan 1 artikel draft untuk uji kebocoran.
        // ------------------------------------------------------------------
        Article::updateOrCreate(
            ['slug' => 'draf-internal-tidak-untuk-dipublikasikan'],
            [
                'category_id' => $this->categoryId('ekonomi'),
                'author_id' => $authorRidho->id,
                'editor_id' => null,
                'title' => 'Draf Internal Tidak Untuk Dipublikasikan',
                'excerpt' => 'Draf internal yang harus disembunyikan dari publik.',
                'content' => '<p>Konten draf internal.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'bandar_lampung',
                'status' => 'draft',
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
                'reading_time_minutes' => 2,
                'views_count' => 0,
                'published_at' => null,
            ],
        );

        // ------------------------------------------------------------------
        // 7. Assign reporter dev ke artikel live_report berstatus live.
        // ------------------------------------------------------------------
        $liveReportIds = Article::where('type', 'live_report')
            ->where('live_status', 'live')
            ->pluck('id');

        $devReporter->assignedLiveReports()->syncWithoutDetaching($liveReportIds);
    }

    /**
     * Definisi 12+ artikel published lintas kategori.
     *
     * @param  array<int, User>  $authors
     * @return array<int, array<string, mixed>>
     */
    private function articlesData(array $authors, User $editor): array
    {
        $ridho = $authors[0];
        $dea = $authors[1];

        return [
            // --- Breaking + Headline ---
            [
                'category_id' => $this->categoryId('ekonomi'),
                'author_id' => $ridho->id,
                'editor_id' => $editor->id,
                'title' => 'IHSG Lampung Sentuh Rekor Tertinggi Sepanjang 2026',
                'slug' => 'ihsg-lampung-sentuh-rekor-tertinggi-sepanjang-2026',
                'excerpt' => 'Indeks Harga Saham Gabungan Lampung tembus rekor baru.',
                'content' => '<p>Pasar saham Lampung mencatatkan rekor tertinggi sepanjang tahun 2026.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'bandar_lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => true,
                'is_breaking' => true,
                'is_recommended' => true,
                'is_featured' => true,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 4,
                'views_count' => 1200,
                'published_at' => now()->subHours(2),
                'tags' => ['bandar-lampung'],
            ],
            [
                'category_id' => $this->categoryId('pemerintahan'),
                'author_id' => $ridho->id,
                'editor_id' => $editor->id,
                'title' => 'Gubernur Lampung Lantik Pejabat Eselon II Baru',
                'slug' => 'gubernur-lampung-lantik-pejabat-eselon-ii-baru',
                'excerpt' => 'Gubernur melantik tiga pejabat eselon II di lingkungan Pemprov.',
                'content' => '<p>Gubernur Lampung melantik tiga pejabat eselon II baru hari ini.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => true,
                'is_breaking' => true,
                'is_recommended' => false,
                'is_featured' => false,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 3,
                'views_count' => 850,
                'published_at' => now()->subHours(5),
                'tags' => ['bandar-lampung'],
            ],
            // --- Foto ---
            [
                'category_id' => $this->categoryId('foto'),
                'author_id' => $dea->id,
                'editor_id' => null,
                'title' => 'Galeri Foto: Festival Krakatau 2026',
                'slug' => 'galeri-foto-festival-krakatau-2026',
                'excerpt' => 'Dokumentasi visual Festival Krakatau di Bandar Lampung.',
                'content' => '<p>Festival Krakatau 2026 menampilkan beragam pertunjukan budaya.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'bandar_lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => false,
                'is_breaking' => false,
                'is_recommended' => true,
                'is_featured' => true,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 2,
                'views_count' => 430,
                'published_at' => now()->subHours(20),
                'tags' => ['bandar-lampung'],
            ],
            // --- Bencana Alam ---
            [
                'category_id' => $this->categoryId('bencana-alam'),
                'author_id' => $dea->id,
                'editor_id' => null,
                'title' => 'Banjir Bandang Terjang Lampung Timur, 50 KK Dievakuasi',
                'slug' => 'banjir-bandang-terjang-lampung-timur-50-kk-dievakuasi',
                'excerpt' => 'Banjir bandang menerjang kawasan Lampung Timur.',
                'content' => '<p>Banjir bandang menerjang beberapa desa di Lampung Timur.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => true,
                'is_breaking' => false,
                'is_recommended' => false,
                'is_featured' => false,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 5,
                'views_count' => 980,
                'published_at' => now()->subHours(30),
                'tags' => ['lampung-timur'],
            ],
            // --- Kriminal / Narkoba ---
            [
                'category_id' => $this->categoryId('narkoba'),
                'author_id' => $dea->id,
                'editor_id' => null,
                'title' => 'Polisi Bongkar Jaringan Narkoba di Bandar Lampung',
                'slug' => 'polisi-bongkar-jaringan-narkoba-di-bandar-lampung',
                'excerpt' => 'Tim BNNP mengungkap jaringan narkoba lintas kabupaten.',
                'content' => '<p>BNNP Lampung membongkar jaringan narkoba dengan barang bukti sabu.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'bandar_lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => false,
                'is_breaking' => false,
                'is_recommended' => true,
                'is_featured' => true,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => 'https://cdn.lampunggehnews.com/videos/bongkar-narkoba.mp4',
                'reading_time_minutes' => 4,
                'views_count' => 670,
                'published_at' => now()->subHours(40),
                'tags' => ['bandar-lampung'],
            ],
            // --- Feature ---
            [
                'category_id' => $this->categoryId('feature'),
                'author_id' => $ridho->id,
                'editor_id' => null,
                'title' => 'Kisah Petani Kopi Robusta Lampung Bertahan di Tengah Iklim',
                'slug' => 'kisah-petani-kopi-robusta-lampung-bertahan-di-tengah-iklim',
                'excerpt' => 'Feature tentang ketangguhan petani kopi Lampung.',
                'content' => '<p>Petani kopi Robusta di Lampung terus berjuang menjaga produktivitas.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => false,
                'is_breaking' => false,
                'is_recommended' => false,
                'is_featured' => true,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 6,
                'views_count' => 320,
                'published_at' => now()->subHours(45),
                'tags' => ['lampung-selatan'],
            ],
            // --- Opini ---
            [
                'category_id' => $this->categoryId('opini'),
                'author_id' => $ridho->id,
                'editor_id' => null,
                'title' => 'Opini: Pemuda Lampung dan Tantangan Ekonomi Digital',
                'slug' => 'opini-pemuda-lampung-dan-tantangan-ekonomi-digital',
                'excerpt' => 'Opini tentang peran pemuda Lampung di era digital.',
                'content' => '<p>Pemuda Lampung perlu beradaptasi dengan ekonomi digital.</p>',
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
                'reading_time_minutes' => 5,
                'views_count' => 210,
                'published_at' => now()->subMonths(1),
                'tags' => ['metro'],
            ],
            // --- Olahraga ---
            [
                'category_id' => $this->categoryId('olahraga'),
                'author_id' => $ridho->id,
                'editor_id' => null,
                'title' => 'PSIL Bandar Lampung Juara Liga 3 Nasional',
                'slug' => 'psil-bandar-lampung-juara-liga-3-nasional',
                'excerpt' => 'PSIL tampil sebagai juara Liga 3 nasional.',
                'content' => '<p>PSIL Bandar Lampung berhasil meraih juara Liga 3 nasional.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'bandar_lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => false,
                'is_breaking' => false,
                'is_recommended' => true,
                'is_featured' => true,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => 'https://cdn.lampunggehnews.com/videos/psil-juara.mp4',
                'reading_time_minutes' => 3,
                'views_count' => 1500,
                'published_at' => now()->subMonths(1)->subDays(5),
                'tags' => ['bandar-lampung'],
            ],
            // --- Wisata Alam ---
            [
                'category_id' => $this->categoryId('wisata-alam'),
                'author_id' => $dea->id,
                'editor_id' => null,
                'title' => 'Pemandangan Air Terjun Way Lalaan Pesawaran',
                'slug' => 'pemandangan-air-terjun-way-lalaan-pesawaran',
                'excerpt' => 'Keindahan air terjun Way Lalaan di Pesawaran.',
                'content' => '<p>Air terjun Way Lalaan menjadi destinasi wisata alam unggulan Pesawaran.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'lampung',
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
                'reading_time_minutes' => 4,
                'views_count' => 540,
                'published_at' => now()->subMonths(1)->subDays(10),
                'tags' => ['pesawaran'],
            ],
            // --- Sponsored ---
            [
                'category_id' => $this->categoryId('ekonomi'),
                'author_id' => $ridho->id,
                'editor_id' => null,
                'title' => 'Promo Akhir Tahun Otomotif Lampung',
                'slug' => 'promo-akhir-tahun-otomotif-lampung',
                'excerpt' => 'Promo otomotif dari sponsor resmi Lampung Geh.',
                'content' => '<p>Dealer otomotif Lampung menggelar promo akhir tahun.</p>',
                'type' => 'standard',
                'live_status' => null,
                'region_scope' => 'bandar_lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => false,
                'is_breaking' => false,
                'is_recommended' => false,
                'is_featured' => false,
                'is_sponsored' => true,
                'sponsor_name' => 'PT Otomotif Lampung',
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 2,
                'views_count' => 300,
                'published_at' => now()->subHours(10),
                'tags' => ['bandar-lampung'],
            ],
            // --- Live Report status live ---
            [
                'category_id' => $this->categoryId('bencana-alam'),
                'author_id' => $dea->id,
                'editor_id' => null,
                'title' => 'Live Report: Evakuasi Warga Lampung Timur Pasca Banjir',
                'slug' => 'live-report-evakuasi-warga-lampung-timur-pasca-banjir',
                'excerpt' => 'Update langsung proses evakuasi warga Lampung Timur.',
                'content' => '<p>Proses evakuasi warga korban banjir Lampung Timur berlangsung.</p>',
                'type' => 'live_report',
                'live_status' => 'live',
                'region_scope' => 'lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => false,
                'is_breaking' => true,
                'is_recommended' => false,
                'is_featured' => false,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 3,
                'views_count' => 750,
                'published_at' => now()->subHours(1),
                'tags' => ['lampung-timur'],
            ],
            // --- Live Report status ended ---
            [
                'category_id' => $this->categoryId('olahraga'),
                'author_id' => $ridho->id,
                'editor_id' => null,
                'title' => 'Live Report: Final Liga 3 PSIL vs Persijap',
                'slug' => 'live-report-final-liga-3-psil-vs-persijap',
                'excerpt' => 'Live report pertandingan final Liga 3.',
                'content' => '<p>Pertandingan final Liga 3 antara PSIL dan Persijap telah berakhir.</p>',
                'type' => 'live_report',
                'live_status' => 'ended',
                'region_scope' => 'bandar_lampung',
                'status' => 'published',
                'editor_note' => null,
                'is_headline' => false,
                'is_breaking' => false,
                'is_recommended' => true,
                'is_featured' => true,
                'is_sponsored' => false,
                'sponsor_name' => null,
                'featured_image_path' => null,
                'image_caption' => null,
                'image_credit' => null,
                'video_url' => null,
                'reading_time_minutes' => 4,
                'views_count' => 1100,
                'published_at' => now()->subHours(6),
                'tags' => ['bandar-lampung'],
            ],
        ];
    }

    /**
     * Seed Live Report entries untuk artikel type=live_report.
     */
    private function seedLiveReportEntries(User $author): void
    {
        $liveArticles = Article::where('type', 'live_report')
            ->where('author_id', $author->id)
            ->get();

        foreach ($liveArticles as $article) {
            $existing = LiveReportEntry::where('article_id', $article->id)->count();

            if ($existing >= 2) {
                continue;
            }

            for ($i = 0; $i < 2; $i++) {
                LiveReportEntry::create([
                    'article_id' => $article->id,
                    'author_id' => $author->id,
                    'title' => 'Update '.($i + 1).': '.$article->title,
                    'content' => '<p>Update perkembangan terkini peristiwa.</p>',
                    'image_path' => null,
                    'published_at' => now()->subMinutes(30 - ($i * 15)),
                ]);
            }
        }
    }

    /**
     * Seed komentar approved level-1, balasan level-2, dan 1 pending.
     */
    private function seedComments(): void
    {
        $publishedArticles = Article::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        foreach ($publishedArticles as $article) {
            $existing = Comment::where('article_id', $article->id)->count();

            if ($existing > 0) {
                continue;
            }

            $parentComment = Comment::create([
                'article_id' => $article->id,
                'parent_id' => null,
                'guest_name' => 'Budi Santoso',
                'guest_email' => 'budi.santoso@example.test',
                'guest_website' => null,
                'content' => 'Terima kasih atas beritanya, sangat informatif.',
                'status' => 'approved',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
            ]);

            Comment::create([
                'article_id' => $article->id,
                'parent_id' => $parentComment->id,
                'guest_name' => 'Siti Aminah',
                'guest_email' => 'siti.aminah@example.test',
                'guest_website' => null,
                'content' => 'Setuju, semoga update terus.',
                'status' => 'approved',
                'ip_address' => '127.0.0.2',
                'user_agent' => 'Mozilla/5.0',
            ]);
        }

        // 1 komentar pending pada artikel pertama.
        $firstArticle = $publishedArticles->first();

        if ($firstArticle !== null) {
            $pendingExisting = Comment::where('article_id', $firstArticle->id)
                ->where('status', 'pending')
                ->exists();

            if (! $pendingExisting) {
                Comment::create([
                    'article_id' => $firstArticle->id,
                    'parent_id' => null,
                    'guest_name' => 'Tamu Anonim',
                    'guest_email' => 'anonim@example.test',
                    'guest_website' => null,
                    'content' => 'Komentar ini menunggu moderasi.',
                    'status' => 'pending',
                    'ip_address' => '127.0.0.3',
                    'user_agent' => 'Mozilla/5.0',
                ]);
            }
        }
    }

    /**
     * Ambil category_id berdasarkan slug (dari CategorySeeder).
     */
    private function categoryId(string $slug): int
    {
        /** @var \App\Models\Category|null $category */
        $category = \App\Models\Category::where('slug', $slug)->first();

        return $category?->id ?? \App\Models\Category::query()->value('id') ?? 1;
    }
}
