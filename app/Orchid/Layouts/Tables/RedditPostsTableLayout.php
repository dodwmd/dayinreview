<?php

namespace App\Orchid\Layouts\Tables;

use App\Models\RedditPost;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class RedditPostsTableLayout extends Table
{
    /**
     * @var string
     */
    protected $target = 'recent_reddit_posts';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('title', 'Title')
                ->render(fn (RedditPost $post) => $post->title),

            TD::make('subreddit', 'Subreddit')
                ->render(fn (RedditPost $post) => $post->subreddit),

            TD::make('score', 'Score')
                ->render(fn (RedditPost $post) => $post->score),

            TD::make('created_at', 'Date')
                ->render(fn (RedditPost $post) => $post->created_at->format('Y-m-d')),
        ];
    }
}
