<?php

namespace App\Observers;

use App\Models\Tag;
use App\Observers\Concerns\HandlesPublicRevalidation;

class TagObserver
{
    use HandlesPublicRevalidation;

    public function saved(Tag $tag): void
    {
        $this->flushPublicApiCaches();
    }

    public function deleted(Tag $tag): void
    {
        $this->flushPublicApiCaches();
    }
}
