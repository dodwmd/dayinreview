<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string|null $description
 * @property string|null $thumbnail_url
 * @property string $type (auto, custom)
 * @property string $visibility (private, unlisted, public)
 * @property bool $is_favorite
 * @property int $view_count
 * @property \Illuminate\Support\Carbon|null $last_viewed_at
 * @property \Illuminate\Support\Carbon|null $last_generated_at
 * @property array|null $generation_algorithm
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\YoutubeVideo[] $videos
 */
class Playlist extends Model
{
    /** @use HasFactory<\Database\Factories\PlaylistFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'thumbnail_url',
        'type',
        'visibility',
        'is_favorite',
        'view_count',
        'last_viewed_at',
        'last_generated_at',
        'generation_algorithm',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_favorite' => 'boolean',
        'view_count' => 'integer',
        'last_viewed_at' => 'datetime',
        'last_generated_at' => 'datetime',
        'generation_algorithm' => 'json',
    ];

    /**
     * Get the user that owns the playlist.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the playlist items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class);
    }

    /**
     * Get the playlist items containing videos.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function videos()
    {
        return $this->hasMany(PlaylistItem::class)
            ->where('source_type', YoutubeVideo::class);
    }

    /**
     * Get the categories for the playlist.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            PlaylistCategory::class,
            'category_playlist',
            'playlist_id',
            'playlist_category_id'
        );
    }

    /**
     * Check if the playlist is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Set playlist visibility.
     */
    public function setVisibility(string $visibility): void
    {
        if (! in_array($visibility, ['private', 'unlisted', 'public'])) {
            throw new \InvalidArgumentException('Invalid visibility value');
        }

        $this->visibility = $visibility;
    }
}
