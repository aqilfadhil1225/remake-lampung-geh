<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class PublishScheduledArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish articles whose scheduled published_at has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $articles = Article::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        foreach ($articles as $article) {
            $article->status = 'published';
            $article->save();
        }

        $this->info("Published {$articles->count()} scheduled article(s).");

        return self::SUCCESS;
    }
}
