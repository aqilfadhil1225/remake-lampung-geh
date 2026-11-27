<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    /**
     * Verify a Cloudflare Turnstile token.
     *
     * When no secret key is configured:
     *  - local/testing: accept any non-empty token (for dev & test with Http::fake()).
     *  - other environments: fail-closed (return false).
     */
    public function verify(string $token, ?string $ip = null): bool
    {
        $secret = config('services.turnstile.secret_key');

        if (! empty($secret)) {
            $response = Http::timeout(5)->asForm()->post((string) config('services.turnstile.verify_url'), [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return $response->successful() && $response->json('success') === true;
        }

        if (app()->environment('local', 'testing')) {
            return trim($token) !== '';
        }

        return false;
    }
}
