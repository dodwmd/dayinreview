<?php

namespace App\Orchid\Layouts\Tables;

use App\Models\YoutubeVideo;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class YouTubeVideosTableLayout extends Table
{
    /**
     * @var string
     */
    protected $target = 'recent_youtube_videos';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('title', 'Title')
                ->render(fn (YoutubeVideo $video) => $video->title),

            TD::make('channel_title', 'Channel')
                ->render(fn (YoutubeVideo $video) => $video->channel_title),

            TD::make('view_count', 'Views')
                ->render(fn (YoutubeVideo $video) => $video->view_count),

            TD::make('created_at', 'Date')
                ->render(fn (YoutubeVideo $video) => $video->created_at->format('Y-m-d')),
        ];
    }
}
