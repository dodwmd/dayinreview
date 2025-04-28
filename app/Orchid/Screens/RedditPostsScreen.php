<?php

namespace App\Orchid\Screens;

use App\Models\RedditPost;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RedditPostsScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'posts' => RedditPost::query()
                ->orderBy('created_at', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    #[\Override]
    public function name(): ?string
    {
        return 'Reddit Posts';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    #[\Override]
    public function commandBar(): iterable
    {
        return [
            Button::make('Refresh Data')
                ->icon('bs.arrow-clockwise')
                ->method('refreshData'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    #[\Override]
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('filter.title')
                    ->title('Search by Title')
                    ->placeholder('Enter title keywords'),

                Input::make('filter.subreddit')
                    ->title('Search by Subreddit')
                    ->placeholder('Enter subreddit name'),

                Select::make('filter.has_video')
                    ->title('Has Video')
                    ->options([
                        '' => 'Any',
                        '1' => 'Yes',
                        '0' => 'No',
                    ]),

                Button::make('Filter')
                    ->method('filter'),
            ])->title('Filter Posts'),

            Layout::table('posts', [
                TD::make('id', 'ID')
                    ->render(fn (RedditPost $post) => $post->id)
                    ->sort()
                    ->filter(Input::make()),

                TD::make('title', 'Title')
                    ->render(fn (RedditPost $post) => $post->title)
                    ->sort()
                    ->filter(Input::make()),

                TD::make('subreddit', 'Subreddit')
                    ->render(fn (RedditPost $post) => $post->subreddit)
                    ->sort()
                    ->filter(Input::make()),

                TD::make('score', 'Score')
                    ->render(fn (RedditPost $post) => $post->score)
                    ->sort()
                    ->alignRight(),

                TD::make('has_video', 'Has Video')
                    ->render(function (RedditPost $post) {
                        // Access has_video attribute safely through getAttribute or data array
                        $hasVideo = $post->getAttribute('has_video') ?? false;

                        return $hasVideo ? '✓' : '✗';
                    })
                    ->align('center'),

                TD::make('created_at', 'Created')
                    ->render(fn (RedditPost $post) => $post->created_at->format('Y-m-d H:i'))
                    ->sort(),

                TD::make('actions', 'Actions')
                    ->render(function (RedditPost $post) {
                        return DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->list([
                                Link::make('View on Reddit')
                                    ->icon('bs.box-arrow-up-right')
                                    ->href($post->permalink)
                                    ->target('_blank'),

                                Button::make('View Details')
                                    ->icon('bs.eye')
                                    ->method('viewDetails', ['id' => $post->id]),

                                Button::make('Remove Post')
                                    ->icon('bs.trash')
                                    ->method('removePost', ['id' => $post->id])
                                    ->confirm('Are you sure you want to remove this post?'),
                            ]);
                    }),
            ]),
        ];
    }

    public function filter(Request $request): void
    {
        Toast::info('Posts have been filtered');
    }

    /**
     * @return void
     */
    public function viewDetails(string $id)
    {
        // Use query()->find() instead of findOrFail
        $post = RedditPost::query()->find($id);

        if (! $post) {
            Toast::error('Post not found');

            return;
        }

        Toast::info("Viewing details for post: {$post->title}");
    }

    /**
     * @return void
     */
    public function removePost(string $id)
    {
        // Use query()->find() instead of findOrFail
        $post = RedditPost::query()->find($id);

        if (! $post) {
            Toast::error('Post not found');

            return;
        }

        $post->delete();

        Toast::success('Post has been removed');
    }

    /**
     * Refresh Reddit data
     */
    public function refreshData(): void
    {
        // Here you would typically call your Reddit data fetch service
        // For example: App\Services\RedditService::fetchNewPosts();

        Toast::info('Reddit data refresh has been scheduled');
    }
}
