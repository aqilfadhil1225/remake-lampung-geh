<?php

namespace App\Http\Resources\V1;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CommentResource extends JsonResource
{
    /**
     * Apakah resource ini adalah komentar level-1 (parent_id null).
     *
     * Pada item level-2, field `replies` tidak dimuat.
     */
    private bool $isTopLevel = true;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        $payload = [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'guest_name' => $comment->guest_name,
            'guest_website' => $this->guestWebsite($comment),
            'content' => $comment->content,
            'created_at' => $comment->created_at?->toIso8601String(),
        ];

        if ($this->isTopLevel) {
            /** @var Collection<int, Comment> $replies */
            $replies = $comment->relationLoaded('replies') ? $comment->replies : collect();

            $payload['replies'] = $replies
                ->map(fn (Comment $reply) => (new self($reply))->asReply())
                ->values()
                ->map(fn (self $resource) => $resource->toArray($request))
                ->all();
        }

        return $payload;
    }

    /**
     * Tandai resource ini sebagai balasan level-2 (tanpa field email/ip/ua
     * dan tanpa field `replies`).
     */
    public function asReply(): self
    {
        $this->isTopLevel = false;

        return $this;
    }

    /**
     * Validasi URI `guest_website`; kembalikan null jika tidak valid.
     */
    private function guestWebsite(Comment $comment): ?string
    {
        if ($comment->guest_website === null || $comment->guest_website === '') {
            return null;
        }

        return filter_var($comment->guest_website, FILTER_VALIDATE_URL) !== false
            ? $comment->guest_website
            : null;
    }
}
