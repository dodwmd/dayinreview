<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $subscribable_type
 * @property string $subscribable_id
 * @property string $name
 * @property string|null $description
 * @property string|null $thumbnail_url
 * @property bool $is_favorite
 * @property int $priority
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Subscription extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subscribable_type',
        'subscribable_id',
        'name',
        'description',
        'thumbnail_url',
        'is_favorite',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_favorite' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_favorite' => false,
        'priority' => 0,
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the categories for the subscription.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            SubscriptionCategory::class,
            'category_subscription',
            'subscription_id',
            'subscription_category_id'
        );
    }

    /**
     * Scope a query to only include reddit subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReddit($query)
    {
        return $query->where('subscribable_type', 'reddit');
    }

    /**
     * Scope a query to only include youtube subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeYoutube($query)
    {
        return $query->where('subscribable_type', 'youtube');
    }

    /**
     * Scope a query to only include favorite subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }
}
