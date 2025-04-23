<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $reddit_id
 * @property string $subreddit
 * @property string $title
 * @property string|null $content
 * @property string $author
 * @property string $permalink
 * @property string $url
 * @property int $score
 * @property int $num_comments
 * @property bool $has_youtube_video
 * @property \DateTime $posted_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * 
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, string $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder join(string $table, string $first, string $operator = null, string $second = null)
 */
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
