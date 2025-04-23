<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Display a listing of the user's subscriptions.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $subscriptions = match ($filter) {
            'youtube' => $this->subscriptionService->getYouTubeSubscriptions($user),
            'reddit' => $this->subscriptionService->getRedditSubscriptions($user),
            default => $this->subscriptionService->getAllSubscriptions($user),
        };

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filter' => $filter,
            'youtubeConnected' => ! empty($user->youtube_token),
            'redditConnected' => ! empty($user->reddit_token),
        ]);
    }

    /**
     * Sync YouTube subscriptions.
     */
    public function syncYouTube(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (empty($user->youtube_token)) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Connect your YouTube account first.');
        }

        $this->subscriptionService->syncYouTubeSubscriptions($user);

        return redirect()->route('subscriptions.index', ['filter' => 'youtube'])
            ->with('status', 'youtube-sync-success');
    }

    /**
     * Subscribe to a YouTube channel.
     */
    public function subscribeYouTube(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channel_id' => 'required|string',
        ]);

        $user = $request->user();

        if (empty($user->youtube_token)) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Connect your YouTube account first.');
        }

        $subscription = $this->subscriptionService->subscribeToYouTubeChannel(
            $user,
            $validated['channel_id']
        );

        if ($subscription) {
            return redirect()->route('subscriptions.index', ['filter' => 'youtube'])
                ->with('status', 'subscription-added');
        }

        return redirect()->route('subscriptions.index')
            ->with('error', 'Failed to subscribe to channel.');
    }

    /**
     * Unsubscribe from a channel/subreddit.
     */
    public function destroy(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->getKey()) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Unauthorized action.');
        }

        $filter = $subscription->subscribable_type === 'youtube' ? 'youtube' : 'reddit';

        if ($this->subscriptionService->unsubscribe($user, $subscription->getKey())) {
            return redirect()->route('subscriptions.index', ['filter' => $filter])
                ->with('status', 'subscription-removed');
        }

        return redirect()->route('subscriptions.index')
            ->with('error', 'Failed to unsubscribe.');
    }

    /**
     * Toggle favorite status for a subscription.
     */
    public function toggleFavorite(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();

        if ($subscription->user_id !== $user->getKey()) {
            return redirect()->route('subscriptions.index')
                ->with('error', 'Unauthorized action.');
        }

        $updatedSubscription = $this->subscriptionService->toggleFavorite($user, $subscription->getKey());

        if ($updatedSubscription) {
            $status = $updatedSubscription->is_favorite ? 'added-to-favorites' : 'removed-from-favorites';

            return redirect()->route('subscriptions.index')
                ->with('status', $status);
        }

        return redirect()->route('subscriptions.index')
            ->with('error', 'Failed to update favorite status.');
    }
}
