<?php

use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Subscription routes
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/youtube/sync', [SubscriptionController::class, 'syncYouTube'])->name('subscriptions.youtube.sync');
    Route::post('/subscriptions/youtube', [SubscriptionController::class, 'subscribeYouTube'])->name('subscriptions.youtube.store');
    Route::delete('/subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
    Route::post('/subscriptions/{subscription}/favorite', [SubscriptionController::class, 'toggleFavorite'])->name('subscriptions.toggle-favorite');

    // Playlist routes
    Route::get('/playlists', [PlaylistController::class, 'index'])->name('playlists.index');
    Route::post('/playlists/generate', [PlaylistController::class, 'generate'])->name('playlists.generate');
    Route::get('/playlists/{id}', [PlaylistController::class, 'show'])->name('playlists.show');
    Route::patch('/playlists/{id}/visibility', [PlaylistController::class, 'updateVisibility'])->name('playlists.update-visibility');
    Route::post('/playlists/{id}/videos/{videoId}/watched', [PlaylistController::class, 'markWatched'])->name('playlists.mark-watched');
});

require __DIR__.'/auth.php';
