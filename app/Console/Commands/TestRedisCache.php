<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestRedisCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-redis-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Redis cache functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Redis cache functionality...');

        // Test setting a cache value
        $testKey = 'test_redis_cache_key';
        $testValue = 'This is a test value at '.now()->toDateTimeString();

        $this->info('Setting cache value: '.$testValue);
        Cache::put($testKey, $testValue, 60); // Cache for 60 seconds

        // Test retrieving the cache value
        $retrievedValue = Cache::get($testKey);

        if ($retrievedValue === $testValue) {
            $this->info('Cache test successful!');
            $this->info('Retrieved value: '.$retrievedValue);
        } else {
            $this->error('Cache test failed!');
            $this->error('Expected: '.$testValue);
            $this->error('Got: '.($retrievedValue ?? 'null'));
        }

        // Test cache driver information
        $this->info('Cache driver: '.config('cache.default'));
        $this->info('Redis connection: '.config('database.redis.default.host').':'.
                    config('database.redis.default.port'));

        return Command::SUCCESS;
    }
}
