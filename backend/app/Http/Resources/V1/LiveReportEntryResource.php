<?php

namespace App\Http\Resources\V1;

use App\Models\LiveReportEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveReportEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LiveReportEntry $entry */
        $entry = $this->resource;

        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'content' => $entry->content,
            'image_url' => $this->imageUrl($entry),
            'author_name' => $entry->author?->name ?? '',
            'published_at' => $entry->published_at?->toIso8601String(),
            'time_wib' => $entry->published_at?->timezone('Asia/Jakarta')->format('H:i').' WIB',
        ];
    }

    /**
     * Bangun URL publik penuh untuk gambar entri Live Report.
     */
    private function imageUrl(LiveReportEntry $entry): ?string
    {
        if ($entry->image_path === null || $entry->image_path === '') {
            return null;
        }

        return rtrim((string) config('app.url'), '/').'/storage/'.$entry->image_path;
    }
}
