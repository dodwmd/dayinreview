<?php

namespace App\Orchid\Screens;

use App\Models\Playlist;
use App\Models\PlaylistCategory;
use App\Models\User;
use App\Models\YoutubeVideo;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PlaylistsScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'playlists' => Playlist::with(['categories', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Playlists';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Create Playlist')
                ->icon('bs.plus-circle')
                ->modal('playlistModal')
                ->method('createPlaylist'),

            Button::make('Generate Auto Playlists')
                ->icon('bs.lightning-charge')
                ->method('generateAutoPlaylists'),
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
                    ->title('Type')
                    ->options([
                        'auto' => 'Auto-Generated',
                        'custom' => 'Custom',
                    ]),

                Input::make('filter.name')
                    ->title('Search by Name')
                    ->placeholder('Enter playlist name'),

                Relation::make('filter.category_id')
                    ->title('Filter by Category')
                    ->fromModel(PlaylistCategory::class, 'name')
                    ->value(null)
                    ->placeholder('All Categories'),

                Relation::make('filter.user_id')
                    ->title('Filter by User')
                    ->fromModel(User::class, 'name')
                    ->value(null)
                    ->placeholder('All Users'),

                Button::make('Filter')
                    ->method('filter'),
            ])->title('Filter Playlists'),

            Layout::table('playlists', [
                TD::make('id', 'ID')
                    ->render(fn (Playlist $playlist) => $playlist->id)
                    ->sort(),

                TD::make('type', 'Type')
                    ->render(fn (Playlist $playlist) => $playlist->type === 'auto' ? 'Auto-Generated' : 'Custom')
                    ->sort()
                    ->filter(
                        Select::make()
                            ->options([
                                'auto' => 'Auto-Generated',
                                'custom' => 'Custom',
                            ])
                            ->value('')
                    ),

                TD::make('name', 'Name')
                    ->render(fn (Playlist $playlist) => $playlist->name)
                    ->sort()
                    ->filter(Input::make()),

                TD::make('user', 'Owner')
                    ->render(fn (Playlist $playlist) => $playlist->user ? $playlist->user->name : 'System')
                    ->sort(),

                TD::make('items_count', 'Videos')
                    ->render(fn (Playlist $playlist) => $playlist->items_count ?? 0)
                    ->alignRight(),

                TD::make('categories', 'Categories')
                    ->render(function (Playlist $playlist) {
                        $categories = $playlist->categories()->get();

                        return $categories->map(function ($category) {
                            return "<span class=\"badge bg-secondary me-1\">{$category->getAttribute('name')}</span>";
                        })->implode(' ');
                    }),

                TD::make('created_at', 'Created')
                    ->render(fn (Playlist $playlist) => $playlist->created_at->format('Y-m-d'))
                    ->sort(),

                TD::make('actions', 'Actions')
                    ->render(function (Playlist $playlist) {
                        return DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->list([
                                Link::make('View Videos')
                                    ->route('platform.playlists.items', ['playlist' => $playlist->id])
                                    ->icon('bs.collection-play'),

                                ModalToggle::make('Edit')
                                    ->icon('bs.pencil')
                                    ->modal('playlistEditModal')
                                    ->method('updatePlaylist')
                                    ->parameters([
                                        'id' => $playlist->id,
                                    ]),

                                Button::make('Manage Categories')
                                    ->icon('bs.tags')
                                    ->method('manageCategories', ['id' => $playlist->id]),

                                Button::make('Regenerate')
                                    ->icon('bs.arrow-repeat')
                                    ->method('regeneratePlaylist', ['id' => $playlist->id])
                                    ->canSee($playlist->type === 'auto'),

                                Button::make('Delete')
                                    ->icon('bs.trash')
                                    ->method('deletePlaylist', ['id' => $playlist->id])
                                    ->confirm('Are you sure you want to delete this playlist?'),
                            ]);
                    }),
            ]),

            // Create playlist modal
            Layout::modal('playlistModal', Layout::rows([
                Input::make('name')
                    ->title('Name')
                    ->required()
                    ->placeholder('Enter playlist name'),

                TextArea::make('description')
                    ->title('Description')
                    ->rows(3),

                Select::make('type')
                    ->title('Type')
                    ->options([
                        'auto' => 'Auto-Generated',
                        'custom' => 'Custom',
                    ])
                    ->required(),

                Select::make('content_type')
                    ->title('Content Type')
                    ->options([
                        YoutubeVideo::class => 'YouTube Videos',
                    ])
                    ->value(null)
                    ->canSee(false),

                Relation::make('user_id')
                    ->title('Owner')
                    ->fromModel(User::class, 'name')
                    ->required(),

                CheckBox::make('visibility')
                    ->title('Public Playlist')
                    ->placeholder('Make this playlist visible to all users')
                    ->value(false),

                Relation::make('categories[]')
                    ->title('Categories')
                    ->fromModel(PlaylistCategory::class, 'name')
                    ->multiple(),
            ]))
                ->title('Create New Playlist')
                ->applyButton('Create'),

            // Edit playlist modal
            Layout::modal('playlistEditModal', Layout::rows([
                Input::make('playlist.id')
                    ->type('hidden'),

                Input::make('playlist.name')
                    ->title('Name')
                    ->required(),

                TextArea::make('playlist.description')
                    ->title('Description')
                    ->rows(3),

                Relation::make('playlist.categories[]')
                    ->title('Categories')
                    ->fromModel(PlaylistCategory::class, 'name')
                    ->multiple(),

                CheckBox::make('playlist.visibility')
                    ->title('Public Playlist')
                    ->placeholder('Make this playlist visible to all users'),
            ]))->async('asyncGetPlaylist'),
        ];
    }

    /**
     * Load playlist data for the edit modal.
     */
    public function asyncGetPlaylist(string $id)
    {
        $playlist = Playlist::query()->find($id);

        if (! $playlist) {
            return [
                'playlist' => [],
            ];
        }

        return [
            'playlist' => [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'description' => $playlist->description,
                'visibility' => $playlist->visibility === 'public',
                'categories' => $playlist->categories()->pluck('id')->toArray(),
            ],
        ];
    }

    /**
     * Create a new playlist.
     */
    public function createPlaylist(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:auto,custom',
            'user_id' => 'required|exists:users,id',
        ]);

        $playlist = new Playlist;
        $playlist->name = $request->input('name');
        $playlist->description = $request->input('description');
        $playlist->type = $request->input('type');
        $playlist->user_id = $request->input('user_id');
        $playlist->visibility = $request->boolean('visibility') ? 'public' : 'private';
        $playlist->save();

        if ($request->has('categories')) {
            $playlist->categories()->sync($request->input('categories'));
        }

        Toast::info('Playlist created successfully');
    }

    /**
     * Update an existing playlist.
     */
    public function updatePlaylist(Request $request, string $id)
    {
        $playlist = Playlist::query()->find($id);

        if (! $playlist) {
            Toast::error('Playlist not found');

            return;
        }

        $playlist->update($request->input('playlist'));

        if ($request->has('playlist.categories')) {
            $playlist->categories()->sync($request->input('playlist.categories'));
        }

        Toast::info('Playlist updated successfully');
    }

    /**
     * Delete a playlist.
     */
    public function deletePlaylist(string $id)
    {
        $playlist = Playlist::query()->find($id);

        if (! $playlist) {
            Toast::error('Playlist not found');

            return;
        }

        $playlist->delete();
        Toast::info('Playlist deleted');
    }

    /**
     * Manage playlist categories.
     */
    public function manageCategories(string $id)
    {
        $playlist = Playlist::query()->find($id);

        if (! $playlist) {
            Toast::error('Playlist not found');

            return;
        }

        // Redirect or show modal to manage categories
        Toast::info("Managing categories for playlist: {$playlist->name}");
    }

    /**
     * Regenerate auto playlist items.
     */
    public function regeneratePlaylist(string $id)
    {
        $playlist = Playlist::query()->find($id);

        if (! $playlist) {
            Toast::error('Playlist not found');

            return;
        }

        // Logic to regenerate auto playlist
        Toast::info('Playlist regeneration started. This may take a few moments.');
    }

    /**
     * Generate auto playlists for all users.
     */
    public function generateAutoPlaylists()
    {
        // Logic to generate auto playlists
        Toast::info('Auto playlist generation scheduled for all users');
    }

    /**
     * Filter playlists based on request.
     */
    public function filter()
    {
        Toast::info('Filter applied');
    }
}
