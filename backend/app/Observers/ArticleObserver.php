<?php

namespace App\Observers;

use App\Models\Article;
use App\Observers\Concerns\HandlesPublicRevalidation;

class ArticleObserver
{
    use HandlesPublicRevalidation;

    public function saved(Article $article): void
    {
        $isPublished = $article->status === 'published';
        $wasPublished = $article->getOriginal('status') === 'published';
        $becamePublished = $isPublished
            && ! $wasPublished
            && ($article->wasChanged('status') || $article->getOriginal('status') === null);
        $wasArchived = $article->status === 'archived'
            && $article->wasChanged('status')
            && $wasPublished;

        if ($wasArchived) {
            $this->flushPublicApiCaches();
            $this->dispatchNextRevalidation(
                'article.archived',
                $this->articleRevalidationPaths($article),
                $this->articleRevalidationTags($article),
            );

            return;
        }

        if (! $isPublished) {
            return;
        }

        $event = $becamePublished
            ? 'article.published'
            : 'article.updated';

        $this->flushPublicApiCaches();
        $this->dispatchNextRevalidation(
            $event,
            $this->articleRevalidationPaths($article),
            $this->articleRevalidationTags($article),
        );
    }

    public function deleted(Article $article): void
    {
        if ($article->status !== 'published') {
            return;
        }

        $this->flushPublicApiCaches();
        $this->dispatchNextRevalidation(
            'article.archived',
            $this->articleRevalidationPaths($article),
            $this->articleRevalidationTags($article),
        );
    }
}
