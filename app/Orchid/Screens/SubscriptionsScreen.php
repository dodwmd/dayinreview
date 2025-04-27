<?php

namespace App\Orchid\Screens;

use App\Models\Subscription;
use App\Models\SubscriptionCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SubscriptionsScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'subscriptions' => Subscription::with('categories')
                ->orderBy('created_at', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Subscriptions';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Add Subscription')
                ->icon('bs.plus-circle')
                ->modal('subscriptionModal')
                ->method('createSubscription'),

            Button::make('Sync Subscriptions')
                ->icon('bs.arrow-repeat')
                ->method('syncSubscriptions'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('filter.type')
                    ->title('Subscription Type')
                    ->options([
                        'App\\Models\\YouTubeChannel' => 'YouTube Channel',
                        'App\\Models\\RedditSubreddit' => 'Reddit Subreddit',
                    ]),

                Relation::make('filter.category_id')
                    ->title('Filter by Category')
                    ->fromModel(SubscriptionCategory::class, 'name')
                    ->value(null)
                    ->placeholder('All Categories'),

                Button::make('Filter')
                    ->method('filter'),
            ])->title('Filter Subscriptions'),

            Layout::table('subscriptions', [
                TD::make('id', 'ID')
                    ->render(fn (Subscription $sub) => $sub->id)
                    ->sort(),

                TD::make('type', 'Type')
                    ->render(function (Subscription $sub) {
                        // Access type safely using getAttribute() or indirect access
                        $type = class_basename($sub->getAttribute('source_type') ?? '');

                        return $type === 'YouTubeChannel' ? 'YouTube Channel' : 'Reddit Subreddit';
                    })
                    ->sort()
                    ->filter(
                        Select::make()
                            ->options([
                                'App\\Models\\YouTubeChannel' => 'YouTube Channel',
                                'App\\Models\\RedditSubreddit' => 'Reddit Subreddit',
                            ])
                    ),

                TD::make('name', 'Name')
                    ->render(function (Subscription $sub) {
                        // Access name safely using getAttribute
                        return $sub->getAttribute('name') ?? '';
                    })
                    ->sort()
                    ->filter(Input::make()),

                TD::make('user', 'Owner')
                    ->render(function (Subscription $sub) {
                        // Access user safely through relationship method
                        $user = $sub->user()->first();

                        return $user ? $user->getAttribute('name') : 'System';
                    })
                    ->sort(),

                TD::make('categories', 'Categories')
                    ->render(function (Subscription $sub) {
                        // Access categories through relationship method
                        $categories = $sub->categories()->get();

                        return $categories->map(function ($category) {
                            return "<span class=\"badge bg-secondary me-1\">{$category->getAttribute('name')}</span>";
                        })->implode(' ');
                    }),

                TD::make('is_favorite', 'Favorite')
                    ->render(fn (Subscription $sub) => $sub->is_favorite ? '★' : '☆')
                    ->align('center'),

                TD::make('actions', 'Actions')
                    ->render(function (Subscription $sub) {
                        return DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->list([
                                ModalToggle::make('Edit')
                                    ->icon('bs.pencil')
                                    ->modal('subscriptionEditModal')
                                    ->method('updateSubscription')
                                    ->parameters([
                                        'id' => $sub->id,
                                    ]),

                                Button::make('Manage Categories')
                                    ->icon('bs.tags')
                                    ->method('manageCategories', ['id' => $sub->id]),

                                Button::make('Toggle Favorite')
                                    ->icon('bs.star')
                                    ->method('toggleFavorite', ['id' => $sub->id]),

                                Button::make('Refresh Content')
                                    ->icon('bs.arrow-clockwise')
                                    ->method('refreshContent', ['id' => $sub->id]),

                                Button::make('Delete')
                                    ->icon('bs.trash')
                                    ->method('deleteSubscription', ['id' => $sub->id])
                                    ->confirm('Are you sure you want to delete this subscription?'),
                            ]);
                    }),
            ]),

            // Add subscription modal
            Layout::modal('subscriptionModal', Layout::rows([
                Select::make('type')
                    ->title('Type')
                    ->options([
                        'youtube' => 'YouTube Channel',
                        'reddit' => 'Reddit Subreddit',
                    ])
                    ->required(),

                Input::make('channel_id')
                    ->title('YouTube Channel ID')
                    ->placeholder('Enter the YouTube channel ID or URL')
                    ->help('e.g., UCxxx or https://www.youtube.com/channel/UCxxx')
                    // Fix canSee() to use a bool value
                    ->canSee(true),

                Input::make('subreddit')
                    ->title('Subreddit')
                    ->placeholder('Enter the subreddit name (without r/)')
                    ->help('e.g., askscience')
                    // Fix canSee() to use a bool value
                    ->canSee(true),

                Relation::make('user_id')
                    ->title('Owner')
                    ->fromModel(User::class, 'name')
                    ->required(),

                Relation::make('categories[]')
                    ->title('Categories')
                    ->fromModel(SubscriptionCategory::class, 'name')
                    ->multiple(),
            ]))
                ->title('Add New Subscription')
                ->applyButton('Add Subscription'),

            // Edit subscription modal
            Layout::modal('subscriptionEditModal', Layout::rows([
                Input::make('subscription.id')
                    ->type('hidden'),

                Input::make('subscription.name')
                    ->title('Name')
                    ->required(),

                Select::make('subscription.is_favorite')
                    ->title('Favorite')
                    ->options([
                        0 => 'No',
                        1 => 'Yes',
                    ]),

                Relation::make('subscription.categories[]')
                    ->title('Categories')
                    ->fromModel(SubscriptionCategory::class, 'name')
                    ->multiple(),
            ]))
                ->title('Edit Subscription')
                ->applyButton('Update Subscription')
                ->async('asyncGetSubscription'),
        ];
    }

    /**
     * Filter subscriptions based on request.
     */
    public function filter(Request $request)
    {
        Toast::info('Subscriptions filtered');
    }

    /**
     * Load subscription data for edit modal.
     */
    public function asyncGetSubscription(string $id)
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            return [
                'subscription' => [],
            ];
        }

        return [
            'subscription' => [
                'id' => $subscription->id,
                'name' => $subscription->name,
                'is_favorite' => $subscription->is_favorite,
                'categories' => $subscription->categories()->pluck('id')->toArray(),
            ],
        ];
    }

    /**
     * Create a new subscription.
     */
    public function createSubscription(Request $request)
    {
        $request->validate([
            'type' => 'required|in:youtube,reddit',
            'user_id' => 'required|exists:users,id',
        ]);

        $type = $request->input('type');
        $userId = $request->input('user_id');

        if ($type === 'youtube') {
            $request->validate(['channel_id' => 'required|string']);
            // Logic to fetch YouTube channel data and create the subscription
            Toast::info('YouTube channel subscription created');
        } elseif ($type === 'reddit') {
            $request->validate(['subreddit' => 'required|string']);
            // Logic to fetch Reddit subreddit data and create the subscription
            Toast::info('Reddit subreddit subscription created');
        }
    }

    /**
     * Update an existing subscription.
     */
    public function updateSubscription(Request $request, string $id)
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            Toast::error('Subscription not found');

            return;
        }

        $subscription->update($request->input('subscription'));

        if ($request->has('subscription.categories')) {
            $subscription->categories()->sync($request->input('subscription.categories'));
        }

        Toast::info('Subscription updated successfully');
    }

    /**
     * Delete a subscription.
     */
    public function deleteSubscription(string $id)
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            Toast::error('Subscription not found');

            return;
        }

        $subscription->delete();
        Toast::info('Subscription deleted successfully');
    }

    /**
     * Toggle the favorite status of a subscription.
     */
    public function toggleFavorite(string $id)
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            Toast::error('Subscription not found');

            return;
        }

        $subscription->is_favorite = ! $subscription->is_favorite;
        $subscription->save();

        Toast::info($subscription->is_favorite ? 'Added to favorites' : 'Removed from favorites');
    }

    /**
     * Sync subscriptions with external sources.
     */
    public function syncSubscriptions()
    {
        // Logic to sync all subscriptions with YouTube and Reddit
        Toast::info('Subscription sync started');
    }

    /**
     * Refresh content for a specific subscription.
     */
    public function refreshContent(string $id)
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            Toast::error('Subscription not found');

            return;
        }

        // Logic to refresh content for this subscription
        Toast::info('Content refresh started for: '.$subscription->getAttribute('name'));
    }

    /**
     * Manage categories for a subscription.
     */
    public function manageCategories(string $id)
    {
        $subscription = Subscription::query()->find($id);

        if (! $subscription) {
            Toast::error('Subscription not found');

            return;
        }

        // Logic to manage categories
        Toast::info('Managing categories for: '.$subscription->getAttribute('name'));
    }
}
