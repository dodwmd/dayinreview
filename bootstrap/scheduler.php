<?php

use App\Console\Commands\AggregateContent;
use Illuminate\Support\Facades\Schedule;

Schedule::command(AggregateContent::class . ' --trending')
    ->daily()
    ->at('03:00') // Run at 3 AM to avoid peak hours
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'))
    ->emailOutputOnFailure(config('mail.admin_address'));

// Also fetch fresh content from specific popular subreddits twice per day
Schedule::command(AggregateContent::class . ' --subreddit=videos --subreddit=music --timeframe=day')
    ->twiceDaily(9, 21) // Run at 9 AM and 9 PM
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
