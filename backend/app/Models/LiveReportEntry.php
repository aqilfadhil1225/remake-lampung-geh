<?php

namespace App\Models;

use Database\Factories\LiveReportEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveReportEntry extends Model
{
    /** @use HasFactory<LiveReportEntryFactory> */
    use HasFactory;
    protected $fillable = [
        'article_id',
        'author_id',
        'title',
        'content',
        'image_path',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
