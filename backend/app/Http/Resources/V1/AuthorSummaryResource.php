<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $author */
        $author = $this->resource;

        return [
            'id' => $author->id,
            'username' => $author->username,
            'name' => $author->name,
            'bio' => $author->bio,
            'avatar_url' => $author->avatar_path !== null
                ? rtrim((string) config('app.url'), '/').'/storage/'.$author->avatar_path
                : null,
        ];
    }
}
