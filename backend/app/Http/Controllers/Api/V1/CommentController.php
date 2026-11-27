<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCommentRequest;
use App\Models\Article;
use App\Models\Comment;
use App\Services\TurnstileVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CommentController
{
    public function __construct(private readonly TurnstileVerifier $turnstileVerifier)
    {
    }

    public function store(StoreCommentRequest $request, string $id): JsonResponse
    {
        if (! ctype_digit($id)) {
            return response()->json(['message' => 'Artikel tidak ditemukan.'], 404);
        }

        $article = Article::where('status', 'published')
            ->whereNotNull('published_at')
            ->find((int) $id);

        if (! $article) {
            return response()->json(['message' => 'Artikel tidak ditemukan.'], 404);
        }

        $validated = $request->validated();

        if (! $this->turnstileVerifier->verify($validated['turnstile_token'], $request->ip())) {
            return response()->json([
                'message' => 'Verifikasi keamanan gagal.',
                'errors' => [
                    'turnstile_token' => ['Token Turnstile tidak valid.'],
                ],
            ], 422);
        }

        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null) {
            $parent = Comment::where('article_id', $article->id)
                ->where('status', 'approved')
                ->whereNull('parent_id')
                ->find($validated['parent_id']);

            if (! $parent) {
                return response()->json([
                    'message' => 'Data yang dikirim tidak valid.',
                    'errors' => [
                        'parent_id' => ['Komentar induk tidak valid atau melebihi batas balasan 2 level.'],
                    ],
                ], 422);
            }
        }

        $content = trim(strip_tags($validated['content']));

        if (mb_strlen($content) < 3) {
            return response()->json([
                'message' => 'Data yang dikirim tidak valid.',
                'errors' => [
                    'content' => ['Konten komentar minimal 3 karakter setelah sanitasi.'],
                ],
            ], 422);
        }

        $comment = Comment::create([
            'article_id' => $article->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'],
            'guest_website' => $validated['guest_website'] ?? null,
            'content' => $content,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);

        return response()->json([
            'message' => 'Komentar Anda telah terkirim dan sedang menunggu moderasi redaksi.',
            'data' => [
                'id' => $comment->id,
                'status' => 'pending',
                'created_at' => $comment->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
