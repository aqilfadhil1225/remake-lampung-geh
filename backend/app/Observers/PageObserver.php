<?php

namespace App\Observers;

use App\Models\Page;
use App\Observers\Concerns\HandlesPublicRevalidation;

class PageObserver
{
    use HandlesPublicRevalidation;

    public function saved(Page $page): void
    {
        $this->dispatchNextRevalidation(
            'page.updated',
            ['/'.$page->slug.'/'],
            ['page:'.$page->slug],
        );
    }
}
