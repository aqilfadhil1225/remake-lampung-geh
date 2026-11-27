<?php

namespace App\Observers;

use App\Models\LiveReportEntry;
use App\Observers\Concerns\HandlesPublicRevalidation;

class LiveReportEntryObserver
{
    use HandlesPublicRevalidation;

    public function created(LiveReportEntry $entry): void
    {
        $article = $entry->article;

        if ($article === null || $article->status !== 'published') {
            return;
        }

        $this->flushPublicApiCaches();
        $this->dispatchNextRevalidation(
            'live_report.entry_added',
            $this->articleRevalidationPaths($article),
            ['home', 'sidebar', 'article:'.$article->id],
        );
    }
}
