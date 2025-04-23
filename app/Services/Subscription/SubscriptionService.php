<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Services\YouTube\YouTubeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        private readonly YouTubeService $youTubeService
    ) {}

    /**
     * Get all subscriptions for a user.
     */
    public function getAllSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->orderBy('is_favorite', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get YouTube subscriptions for a user.
     */
    public function getYouTubeSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->youtube()
            ->orderBy('is_favorite', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get Reddit subscriptions for a user.
     */
    public function getRedditSubscriptions(User $user): Collection
    {
        return $user->subscriptions()
            ->reddit()
            ->orderBy('is_favorite', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Sync YouTube subscriptions from YouTube API.
     */
    public function syncYouTubeSubscriptions(User $user): Collection
    {
        if (empty($user->youtube_token)) {
            Log::warning('Attempted to sync YouTube subscriptions for user without YouTube token', [
                'user_id' => $user->getKey(),
            ]);

            return collect();
        }

        try {
            // Fetch subscriptions from YouTube
            $channels = $this->youTubeService->getUserSubscriptions($user);

            // Current user's YouTube subscriptions
            $existingSubscriptions = $user->subscriptions()
                ->youtube()
                ->pluck('subscribable_id')
                ->toArray();

            // Add new subscriptions
            foreach ($channels as $channel) {
                if (! in_array($channel['id'], $existingSubscriptions)) {
                    $user->subscriptions()->create([
                        'subscribable_type' => 'youtube',
                        'subscribable_id' => $channel['id'],
                        'name' => $channel['title'],
                        'description' => $channel['description'] ?? null,
                        'thumbnail_url' => $channel['thumbnail'] ?? null,
                    ]);
                }
            }

            // Clean up removed subscriptions
            $channelIds = collect($channels)->pluck('id')->toArray();
            $user->subscriptions()
                ->youtube()
                ->whereNotIn('subscribable_id', $channelIds)
                ->delete();

            return $this->getYouTubeSubscriptions($user);
        } catch (\Exception $e) {
            Log::error('Failed to sync YouTube subscriptions', [
                'user_id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Subscribe to a YouTube channel.
     */
    public function subscribeToYouTubeChannel(User $user, string $channelId): ?Subscription
    {
        try {
            // Check if subscription already exists
            $existingSubscription = $user->subscriptions()
                ->youtube()
                ->where('subscribable_id', $channelId)
                ->first();

            if ($existingSubscription) {
                return $existingSubscription;
            }

            // Fetch channel details from YouTube
            $channelInfo = $this->youTubeService->getChannelInfo($channelId);

            if (! $channelInfo) {
                Log::warning('YouTube channel not found', ['channel_id' => $channelId]);

                return null;
            }

            // Create subscription
            return $user->subscriptions()->create([
                'subscribable_type' => 'youtube',
                'subscribable_id' => $channelId,
                'name' => $channelInfo['title'],
                'description' => $channelInfo['description'] ?? null,
                'thumbnail_url' => $channelInfo['thumbnail'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to YouTube channel', [
                'user_id' => $user->getKey(),
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Unsubscribe from a channel or subreddit.
     */
    public function unsubscribe(User $user, string $subscriptionId): bool
    {
        try {
            return $user->subscriptions()
                ->where('id', $subscriptionId)
                ->delete() > 0;
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe', [
                'user_id' => $user->getKey(),
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Toggle favorite status for a subscription.
     */
    public function toggleFavorite(User $user, string $subscriptionId): ?Subscription
    {
        try {
            $subscription = $user->subscriptions()->findOrFail($subscriptionId);
            $subscription->is_favorite = ! $subscription->is_favorite;
            $subscription->save();

            return $subscription;
        } catch (\Exception $e) {
            Log::error('Failed to toggle favorite status', [
                'user_id' => $user->getKey(),
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
