<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Services\YouTube\YouTubeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    protected YouTubeService $youTubeApiService;

    public function __construct(YouTubeService $youTubeApiService)
    {
        $this->youTubeApiService = $youTubeApiService;
    }

    /**
     * Get all subscriptions for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription>
     */
    public function getAllSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get YouTube subscriptions for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription>
     */
    public function getYouTubeSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->where('subscribable_type', 'youtube')
            ->orderBy('is_favorite', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get Reddit subscriptions for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription>
     */
    public function getRedditSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->where('subscribable_type', 'reddit')
            ->orderBy('is_favorite', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Sync YouTube subscriptions from YouTube API.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription>
     */
    public function syncYouTubeSubscriptions(User $user): Collection
    {
        if (empty($user->youtube_token)) {
            Log::warning('Attempted to sync YouTube subscriptions for user without YouTube token', [
                'user_id' => $user->id,
            ]);

            // Return empty Eloquent collection
            return Subscription::query()->whereRaw('1 = 0')->get();
        }

        try {
            // Get existing subscriptions to avoid duplicates
            $existingChannelIds = $user->subscriptions()
                ->where('subscribable_type', 'youtube')
                ->get()
                ->pluck('subscribable_id')
                ->toArray();

            // Fetch from YouTube API
            $channels = $this->youTubeApiService->getUserSubscriptions($user);

            // Create subscriptions for any new channels
            foreach ($channels as $channel) {
                if (! in_array($channel['id'], $existingChannelIds)) {
                    $this->subscribeToYouTubeChannel($user, $channel['id'], $channel['title']);
                }
            }

            // Return the updated list
            return $this->getYouTubeSubscriptions($user);
        } catch (\Exception $e) {
            Log::error('Failed to sync YouTube subscriptions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Return empty Eloquent collection
            return Subscription::query()->whereRaw('1 = 0')->get();
        }
    }

    /**
     * Subscribe to a YouTube channel.
     */
    public function subscribeToYouTubeChannel(User $user, string $channelId, string $channelTitle): ?Subscription
    {
        try {
            // Create the subscription directly
            $subscription = new Subscription;
            $subscription->user_id = (string) $user->id; // Cast to string to ensure UUID compatibility
            $subscription->subscribable_type = 'youtube';
            $subscription->subscribable_id = $channelId;
            $subscription->name = $channelTitle;
            $subscription->is_favorite = false;
            $subscription->save();

            return $subscription;
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to YouTube channel', [
                'user_id' => $user->id,
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Toggle favorite status of a subscription.
     */
    public function toggleFavorite(string $subscriptionId): ?Subscription
    {
        try {
            $subscription = Subscription::query()->find($subscriptionId);

            if (! $subscription) {
                return null;
            }

            // Toggle the is_favorite flag
            $isFavorite = (bool) $subscription->getAttribute('is_favorite');
            $subscription->is_favorite = ! $isFavorite;
            $subscription->save();

            return $subscription;
        } catch (\Exception $e) {
            Log::error('Failed to toggle subscription favorite status', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
