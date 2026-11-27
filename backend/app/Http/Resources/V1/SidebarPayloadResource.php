<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SidebarPayloadResource extends JsonResource
{
    /**
     * Payload widget sidebar: recent posts, recent comments, monthly archives.
     *
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'recent_posts' => ArticleCardResource::collection($data['recent_posts']),
            'recent_comments' => $data['recent_comments'],
            'monthly_archives' => $data['monthly_archives'],
        ];
    }
}
