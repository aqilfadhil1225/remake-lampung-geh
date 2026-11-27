<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor', 'reporter']);
    }

    public function view(User $user, Article $article): bool
    {
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        return $user->hasRole('reporter') && $article->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor', 'reporter']);
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        return $user->hasRole('reporter')
            && $article->author_id === $user->id
            && in_array($article->status, ['draft', 'revision_needed'], true);
    }

    public function delete(User $user, Article $article): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('editor')) {
            return in_array($article->status, ['draft', 'in_review', 'revision_needed'], true);
        }

        return false;
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }

    public function publish(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }
}
