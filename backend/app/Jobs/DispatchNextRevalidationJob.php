<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchNextRevalidationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $event,
        public array $paths = [],
        public array $tags = [],
    ) {}

    public function backoff(): array
    {
        return [2, 5];
    }

    public function handle(): void
    {
        $enabled = (bool) config('services.next_revalidate.enabled');
        $url = (string) config('services.next_revalidate.url', '');
        $secret = (string) config('services.next_revalidate.secret', '');

        if (! $enabled || $url === '' || $secret === '') {
            return;
        }

        Http::timeout((int) config('services.next_revalidate.timeout', 5))
            ->withHeaders(['X-Revalidate-Secret' => $secret])
            ->post($url, [
                'event' => $this->event,
                'paths' => array_values(array_unique($this->paths)),
                'tags' => array_values(array_unique($this->tags)),
            ])
            ->throw();
    }
}
