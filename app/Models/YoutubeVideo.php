<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $youtube_id
 * @property string $reddit_post_id
 * @property string $title
 * @property string $description
 * @property string $channel_id
 * @property string $channel_title
 * @property string $thumbnail_url
 * @property int $duration_seconds
 * @property int $view_count
 * @property int $like_count
 * @property bool $is_trending
 * @property \DateTime $published_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, string $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder join(string $table, string $first, string $operator = null, string $second = null)
 */
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
