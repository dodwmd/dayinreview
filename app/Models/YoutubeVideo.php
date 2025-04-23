<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeVideo extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'youtube_id',
        'reddit_post_id',
        'title',
        'description',
        'channel_id',
        'channel_title',
        'thumbnail_url',
        'duration_seconds',
        'view_count',
        'like_count',
        'is_trending',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_trending' => 'boolean',
        'published_at' => 'datetime',
        'duration_seconds' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
    ];

    /**
     * Get the Reddit post associated with this YouTube video.
     */
    public function redditPost(): BelongsTo
    {
        return $this->belongsTo(RedditPost::class, 'reddit_post_id');
    }
}
