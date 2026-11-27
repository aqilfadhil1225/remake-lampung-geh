<?php

namespace App\Observers;

use App\Models\Comment;
use App\Observers\Concerns\HandlesPublicRevalidation;

class CommentObserver
{
    use HandlesPublicRevalidation;

    public function saved(Comment $comment): void
    {
        $isApproved = $comment->status === 'approved';
        $wasApproved = $comment->wasChanged('status')
            && $comment->getOriginal('status') === 'approved';

        if (! $isApproved && ! $wasApproved) {
            return;
        }

        $article = $comment->article;

        if ($article === null) {
            return;
        }

        $this->flushHomeAndSidebarCaches();
        $this->dispatchNextRevalidation(
            'comment.approved',
            $this->articleRevalidationPaths($article),
            ['sidebar', 'article:'.$article->id],
        );
    }
}
