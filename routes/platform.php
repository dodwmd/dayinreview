<?php

declare(strict_types=1);

use App\Orchid\Screens\ContentDashboard;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\PlaylistItemsScreen;
use App\Orchid\Screens\PlaylistsScreen;
use App\Orchid\Screens\RedditPostsScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\SubscriptionsScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use App\Orchid\Screens\YouTubeVideosScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Main
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

// Day in Review Dashboard
Route::screen('/dashboard', ContentDashboard::class)
    ->name('platform.content.dashboard')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Content Dashboard', route('platform.content.dashboard')));

// Reddit Posts
Route::screen('/content/reddit', RedditPostsScreen::class)
    ->name('platform.content.reddit')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.content.dashboard')
        ->push('Reddit Posts', route('platform.content.reddit')));

// YouTube Videos
Route::screen('/content/youtube', YouTubeVideosScreen::class)
    ->name('platform.content.youtube')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.content.dashboard')
        ->push('YouTube Videos', route('platform.content.youtube')));

// Subscriptions
Route::screen('/subscriptions', SubscriptionsScreen::class)
    ->name('platform.subscriptions')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.content.dashboard')
        ->push('Subscriptions', route('platform.subscriptions')));

// Playlists
Route::screen('/playlists', PlaylistsScreen::class)
    ->name('platform.playlists')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.content.dashboard')
        ->push('Playlists', route('platform.playlists')));

// Playlist Items
Route::screen('/playlists/{playlist}/items', PlaylistItemsScreen::class)
    ->name('platform.playlists.items')
    ->breadcrumbs(fn (Trail $trail, $playlist) => $trail
        ->parent('platform.playlists')
        ->push('Videos', route('platform.playlists.items', $playlist)));

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

// Example routes removed - use Day in Review screens instead
