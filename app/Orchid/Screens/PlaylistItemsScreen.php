<?php

namespace App\Orchid\Screens;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\YoutubeVideo;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * PlaylistItemsScreen displays videos in a playlist.
 *
 * @property Playlist $playlist The playlist being displayed
 */
class PlaylistItemsScreen extends Screen
{
    /**
     * @var Playlist
     */
    public $playlist;

    /**
     * Query the playlist and items to display.
     *
     * @param  Playlist  $playlist  The playlist to display
     * @return array
     */
    public function query(Playlist $playlist): iterable
    {
        $this->playlist = $playlist;

        $items = $playlist->items()
            // Use where() as filter() is not recognized by PHPStan
            ->orderBy('position')
            ->paginate();

        return [
            'playlist' => $playlist,
            'items' => $items,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->playlist->name.' - Videos';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Manage videos in this playlist';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Back to Playlists')
                ->icon('bs.arrow-left')
                ->route('platform.playlists'),

            Button::make('Add Video')
                ->icon('bs.plus')
                ->method('showAddVideoModal'),

            Button::make('Update Position')
                ->icon('bs.sort-numeric-down')
                ->method('updatePositions'),

            Button::make('Remove All')
                ->icon('bs.trash')
                ->method('removeAllVideos')
                ->confirm('Are you sure you want to remove all videos from this playlist?'),
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
            Layout::table('items', [
                TD::make('position', 'Position')
                    ->sort()
                    ->render(fn (PlaylistItem $item) => Input::make('positions['.$item->id.']')
                        ->type('number')
                        ->value($item->position)
                        ->min(0)
                        ->max(999)
                        ->step(1)),

                TD::make('video.thumbnail', 'Thumbnail')
                    ->render(function (PlaylistItem $item) {
                        // Access the morphTo relationship "source" instead of directly accessing "video"
                        $source = $item->source;
                        if (! $source || ! $source instanceof YoutubeVideo) {
                            return 'No video';
                        }

                        return "<img src='{$source->thumbnail_url}' width='120' height='68' alt='Thumbnail'>";
                    })->width('130px'),

                TD::make('video.title', 'Title')
                    ->render(function (PlaylistItem $item) {
                        // Access the morphTo relationship "source" instead of directly accessing "video"
                        $source = $item->source;

                        return $source && $source instanceof YoutubeVideo ? $source->title : 'Video not found';
                    })
                    ->sort()
                    ->filter((string) Input::make()),

                TD::make('video.channel_title', 'Channel')
                    ->render(function (PlaylistItem $item) {
                        // Access the morphTo relationship "source" instead of directly accessing "video"
                        $source = $item->source;

                        return $source && $source instanceof YoutubeVideo ? $source->channel_title : '-';
                    })
                    ->sort(),

                TD::make('watched', 'Watched')
                    ->render(function (PlaylistItem $item) {
                        // Use the is_watched property which is defined in the model
                        $checked = $item->is_watched ? 'checked' : '';

                        return "
                            <label class=\"form-check\">
                                <input type=\"checkbox\" class=\"form-check-input toggle-watched\" 
                                    data-id=\"{$item->id}\" 
                                    {$checked}
                                >
                            </label>
                        ";
                    }),

                TD::make('actions', 'Actions')
                    ->render(function (PlaylistItem $item) {
                        // Get the YouTube URL early
                        $youtubeUrl = '#';
                        $source = $item->source;
                        if ($source && $source instanceof YoutubeVideo) {
                            $youtubeUrl = "https://www.youtube.com/watch?v={$source->youtube_id}";
                        }

                        return DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->list([
                                Link::make('View on YouTube')
                                    ->icon('bs.youtube')
                                    ->href($youtubeUrl)
                                    ->target('_blank'),

                                Button::make('Mark as '.($item->is_watched ? 'Unwatched' : 'Watched'))
                                    ->icon($item->is_watched ? 'bs.eye-slash' : 'bs.eye')
                                    ->method('toggleWatched', ['id' => $item->id]),

                                Button::make('Remove')
                                    ->icon('bs.trash')
                                    ->method('removeVideo', ['id' => $item->id])
                                    ->confirm('Are you sure you want to remove this video from the playlist?'),
                            ]);
                    }),
            ]),
        ];
    }

    /**
     * Toggle the watched status of a video.
     *
     * @param  int  $id
     * @return void
     */
    public function toggleWatched(Request $request, $id)
    {
        /** @var PlaylistItem $item */
        $item = PlaylistItem::findOrFail($id);

        // Toggle the is_watched property using the proper name from the model
        $item->is_watched = ! $item->is_watched;
        $item->save();

        Toast::info($item->is_watched ? 'Video marked as watched' : 'Video marked as unwatched');
    }

    /**
     * Remove a video from the playlist.
     *
     * @param  int  $id
     * @return void
     */
    public function removeVideo(Request $request, $id)
    {
        /** @var PlaylistItem $item */
        $item = PlaylistItem::findOrFail($id);
        $item->delete();

        Toast::info('Video removed from playlist');
    }

    /**
     * Update video positions in the playlist.
     *
     * @return void
     */
    public function updatePositions(Request $request)
    {
        $positions = $request->get('positions', []);

        foreach ($positions as $id => $position) {
            /** @var PlaylistItem $item */
            $item = PlaylistItem::findOrFail($id);
            $item->position = (int) $position;
            $item->save();
        }

        Toast::info('Video positions updated');
    }

    /**
     * Remove all videos from the playlist.
     *
     * @return void
     */
    public function removeAllVideos()
    {
        $this->playlist->items()->delete();
        Toast::info('All videos removed from playlist');
    }
}
