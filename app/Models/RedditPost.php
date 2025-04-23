<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RedditPost extends Model
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
        'reddit_id',
        'subreddit',
        'title',
        'content',
        'author',
        'permalink',
        'url',
        'score',
        'num_comments',
        'has_youtube_video',
        'posted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_youtube_video' => 'boolean',
        'posted_at' => 'datetime',
        'score' => 'integer',
        'num_comments' => 'integer',
    ];

    /**
     * Get the YouTube videos associated with this Reddit post.
     */
    public function youtubeVideos(): HasMany
    {
        return $this->hasMany(YoutubeVideo::class, 'reddit_post_id');
    }
}
