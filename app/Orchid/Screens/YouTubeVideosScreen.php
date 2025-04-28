<?php

namespace App\Orchid\Screens;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\YoutubeVideo;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class YouTubeVideosScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'videos' => YoutubeVideo::query()
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
        return 'YouTube Videos';
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

                Input::make('filter.channel_title')
                    ->title('Search by Channel')
                    ->placeholder('Enter channel name'),

                Button::make('Filter')
                    ->method('filter'),
            ])->title('Filter Videos'),

            Layout::table('videos', [
                TD::make('id', 'ID')
                    ->render(fn (YoutubeVideo $video) => $video->id)
                    ->sort(),

                TD::make('thumbnail', 'Thumbnail')
                    ->render(function (YoutubeVideo $video) {
                        return "<img src='{$video->thumbnail_url}' width='120' class='rounded'>";
                    }),

                TD::make('title', 'Title')
                    ->render(fn (YoutubeVideo $video) => $video->title)
                    ->sort()
                    ->filter(Input::make()),

                TD::make('channel_title', 'Channel')
                    ->render(fn (YoutubeVideo $video) => $video->channel_title)
                    ->sort()
                    ->filter(Input::make()),

                TD::make('view_count', 'Views')
                    ->render(fn (YoutubeVideo $video) => number_format($video->view_count))
                    ->sort()
                    ->alignRight(),

                TD::make('created_at', 'Added')
                    ->render(fn (YoutubeVideo $video) => $video->created_at->format('Y-m-d H:i'))
                    ->sort(),

                TD::make('actions', 'Actions')
                    ->render(function (YoutubeVideo $video) {
                        return DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->list([
                                Link::make('View on YouTube')
                                    ->icon('bs.box-arrow-up-right')
                                    ->href("https://www.youtube.com/watch?v={$video->youtube_id}")
                                    ->target('_blank'),

                                ModalToggle::make('Add to Playlist')
                                    ->icon('bs.plus-circle')
                                    ->modal('addToPlaylistModal')
                                    ->method('addToPlaylist')
                                    ->parameters([
                                        'id' => $video->id,
                                    ]),

                                Button::make('View Details')
                                    ->icon('bs.eye')
                                    ->method('viewDetails', ['id' => $video->id]),

                                Button::make('Delete Video')
                                    ->icon('bs.trash')
                                    ->method('deleteVideo', ['id' => $video->id])
                                    ->confirm('Are you sure you want to delete this video?'),
                            ]);
                    }),
            ]),

            Layout::modal('addToPlaylistModal', Layout::rows([
                Relation::make('playlist_id')
                    ->title('Select Playlist')
                    ->fromModel(Playlist::class, 'name')
                    ->required(),

                Input::make('position')
                    ->title('Position')
                    ->type('number')
                    ->value(0)
                    ->help('Optional: Specify position (0 for the end of playlist)'),
            ]))->title('Add to Playlist')->applyButton('Add'),
        ];
    }

    /**
     * Filter videos based on request.
     */
    public function filter(Request $request): void
    {
        Toast::info('Videos filtered');
    }

    /**
     * View details for a video.
     *
     * @return void
     */
    public function viewDetails(string $id)
    {
        $video = YoutubeVideo::query()->find($id);

        if (! $video) {
            Toast::error('Video not found');

            return;
        }

        Toast::info("Viewing details for video: {$video->title}");
    }

    /**
     * Delete a video.
     *
     * @return void
     */
    public function deleteVideo(string $id)
    {
        $video = YoutubeVideo::query()->find($id);

        if (! $video) {
            Toast::error('Video not found');

            return;
        }

        $video->delete();
        Toast::info('Video deleted successfully');
    }

    /**
     * Add a video to a playlist.
     *
     * @return void
     */
    public function addToPlaylist(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'playlist_id' => 'required|string|exists:playlists,id',
            'position' => 'integer|min:0',
        ]);

        $video = YoutubeVideo::query()->find($request->input('id'));

        if (! $video) {
            Toast::error('Video not found');

            return;
        }

        $playlist = Playlist::query()->find($request->input('playlist_id'));

        if (! $playlist) {
            Toast::error('Playlist not found');

            return;
        }

        $playlistItem = new PlaylistItem;
        $playlistItem->playlist_id = $playlist->id;
        $playlistItem->source_type = YoutubeVideo::class;
        $playlistItem->source_id = $video->id;
        $playlistItem->position = $request->input('position', 0);
        $playlistItem->save();

        Toast::info("Added video to playlist: {$playlist->name}");
    }

    /**
     * Refresh YouTube data.
     */
    public function refreshData(): void
    {
        Toast::info('YouTube data refresh has been scheduled');
    }
}
