<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Article extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'category_id',
        'author_id',
        'editor_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'type',
        'live_status',
        'region_scope',
        'status',
        'editor_note',
        'is_headline',
        'is_breaking',
        'is_recommended',
        'is_featured',
        'is_sponsored',
        'sponsor_name',
        'featured_image_path',
        'image_caption',
        'image_credit',
        'video_url',
        'reading_time_minutes',
        'views_count',
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
            'is_headline' => 'boolean',
            'is_breaking' => 'boolean',
            'is_recommended' => 'boolean',
            'is_featured' => 'boolean',
            'is_sponsored' => 'boolean',
            'reading_time_minutes' => 'integer',
            'views_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function liveReportEntries(): HasMany
    {
        return $this->hasMany(LiveReportEntry::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
