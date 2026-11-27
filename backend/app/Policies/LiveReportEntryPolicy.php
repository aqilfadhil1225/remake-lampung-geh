<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\LiveReportEntry;
use App\Models\User;

class LiveReportEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor', 'reporter']);
    }

    public function view(User $user, LiveReportEntry $entry): bool
    {
        return $user->hasAnyRole(['admin', 'editor', 'reporter']);
    }

    public function create(User $user, ?Article $article = null): bool
    {
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        if (! $user->hasRole('reporter') || $article === null) {
            return false;
        }

        return $article->type === 'live_report'
            && $article->live_status === 'live'
            && $article->liveReportAssignees()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, LiveReportEntry $entry): bool
    {
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        if (! $user->hasRole('reporter') || $entry->author_id !== $user->id) {
            return false;
        }

        $article = $entry->article;

        return $article !== null
            && $article->type === 'live_report'
            && $article->live_status === 'live'
            && $article->liveReportAssignees()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, LiveReportEntry $entry): bool
    {
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        return false;
    }
}
