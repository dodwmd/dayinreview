<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
            Menu::make('Dashboard')
                ->icon('bs.house')
                ->title('Day in Review')
                ->route(config('platform.index')),

            Menu::make('Content Dashboard')
                ->icon('bs.speedometer2')
                ->route('platform.content.dashboard'),

            Menu::make('Reddit Posts')
                ->icon('bs.reddit')
                ->route('platform.content.reddit')
                ->badge(function () {
                    return \App\Models\RedditPost::query()->count();
                }),

            Menu::make('YouTube Videos')
                ->icon('bs.youtube')
                ->route('platform.content.youtube')
                ->badge(function () {
                    return \App\Models\YoutubeVideo::query()->count();
                }),

            Menu::make('Subscriptions')
                ->icon('bs.bookmark')
                ->route('platform.subscriptions')
                ->badge(function () {
                    return \App\Models\Subscription::query()->count();
                }),

            Menu::make('Playlists')
                ->icon('bs.collection-play')
                ->route('platform.playlists')
                ->badge(function () {
                    return \App\Models\Playlist::query()->count();
                })
                ->divider(),

            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),

            Menu::make(__('Roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),

            ItemPermission::group(__('Content'))
                ->addPermission('platform.content.reddit', __('Reddit Posts'))
                ->addPermission('platform.content.youtube', __('YouTube Videos'))
                ->addPermission('platform.subscriptions', __('Subscriptions'))
                ->addPermission('platform.playlists', __('Playlists')),
        ];
    }
}
