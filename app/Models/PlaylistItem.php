<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $playlist_id
 * @property string $source_type
 * @property string $source_id
 * @property int $position
 * @property bool $is_watched
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $added_at
 * @property \Illuminate\Support\Carbon|null $watched_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Playlist $playlist
 * @property-read Model $source
 */
class PlaylistItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'playlist_id',
        'source_type',
        'source_id',
        'position',
        'is_watched',
        'notes',
        'added_at',
        'watched_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'is_watched' => 'boolean',
        'added_at' => 'datetime',
        'watched_at' => 'datetime',
    ];

    /**
     * Get the playlist that owns the item.
     */
    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    /**
     * Get the source model (e.g., YoutubeVideo).
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the item as watched.
     */
    public function markAsWatched(): void
    {
        $this->is_watched = true;
        $this->watched_at = now();
        $this->save();
    }
}
