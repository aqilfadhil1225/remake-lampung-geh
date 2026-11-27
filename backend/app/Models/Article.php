<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Article extends Model implements HasMedia
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

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

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            if ($article->isDirty('reading_time_minutes')) {
                return;
            }

            $wordCount = str_word_count(strip_tags((string) $article->content));
            $article->reading_time_minutes = max(1, (int) ceil($wordCount / 200));
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $conversions = [
            'thumbnail' => [320, 180],
            'card' => [640, 360],
            'detail' => [1440, 810],
            'og_image' => [1200, 630],
        ];

        foreach ($conversions as $name => [$width, $height]) {
            $conversion = $this->addMediaConversion($name)
                ->fit(Fit::Crop, $width, $height)
                ->format('webp');

            if (app()->environment('testing')) {
                $conversion->nonQueued();
            }
        }
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

    /**
     * Reporter yang di-assign ke artikel live report.
     *
     * @return BelongsToMany<User, string>
     */
    public function liveReportAssignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'live_report_assignees')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
