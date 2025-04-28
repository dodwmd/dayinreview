<?php

namespace App\Orchid\Screens;

use App\Models\Playlist;
use App\Models\RedditPost;
use App\Models\Subscription;
use App\Models\User;
use App\Models\YoutubeVideo;
use App\Orchid\Layouts\Metrics\ContentMetricsLayout;
use App\Orchid\Layouts\Tables\RedditPostsTableLayout;
use App\Orchid\Layouts\Tables\YouTubeVideosTableLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class ContentDashboard extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $redditPostCount = RedditPost::query()->count();
        $youtubeVideoCount = YoutubeVideo::query()->count();
        $subscriptionCount = Subscription::query()->count();
        $playlistCount = Playlist::query()->count();
        $userCount = User::query()->count();

        $recentRedditPosts = RedditPost::query()->orderBy('created_at', 'desc')->take(5)->get();
        $recentYouTubeVideos = YoutubeVideo::query()->orderBy('created_at', 'desc')->take(5)->get();

        return [
            'metrics' => [
                'reddit_posts' => [
                    'value' => $redditPostCount,
                    'description' => 'Total Reddit Posts',
                    'icon' => 'reddit',
                ],
                'youtube_videos' => [
                    'value' => $youtubeVideoCount,
                    'description' => 'Total YouTube Videos',
                    'icon' => 'youtube',
                ],
                'subscriptions' => [
                    'value' => $subscriptionCount,
                    'description' => 'Total Subscriptions',
                    'icon' => 'bookmark',
                ],
                'playlists' => [
                    'value' => $playlistCount,
                    'description' => 'Total Playlists',
                    'icon' => 'collection-play',
                ],
                'users' => [
                    'value' => $userCount,
                    'description' => 'Total Users',
                    'icon' => 'people',
                ],
            ],
            'recent_reddit_posts' => $recentRedditPosts,
            'recent_youtube_videos' => $recentYouTubeVideos,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    #[\Override]
    public function name(): ?string
    {
        return 'Content Dashboard';
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
            Link::make('Reddit Posts')
                ->icon('reddit')
                ->route('platform.content.reddit'),

            Link::make('YouTube Videos')
                ->icon('youtube')
                ->route('platform.content.youtube'),

            Link::make('Subscriptions')
                ->icon('bookmark')
                ->route('platform.subscriptions'),

            Link::make('Playlists')
                ->icon('collection-play')
                ->route('platform.playlists'),
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
            ContentMetricsLayout::class,
            RedditPostsTableLayout::class,
            YouTubeVideosTableLayout::class,
        ];
    }
}
