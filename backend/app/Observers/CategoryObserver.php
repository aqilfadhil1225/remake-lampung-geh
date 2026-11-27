<?php

namespace App\Observers;

use App\Models\Category;
use App\Observers\Concerns\HandlesPublicRevalidation;

class CategoryObserver
{
    use HandlesPublicRevalidation;

    public function saved(Category $category): void
    {
        $this->flushPublicApiCaches();
    }

    public function deleted(Category $category): void
    {
        $this->flushPublicApiCaches();
    }
}
