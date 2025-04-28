<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\RateLimiter;

class TestRedisRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-redis-rate-limit {--attempts=5 : Number of attempts to make}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Redis rate limiting functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Redis rate limiting functionality...');

        // Define rate limit parameters
        $key = 'test-rate-limit';
        $maxAttempts = 3;
        $decaySeconds = 30;

        // Clear any existing rate limiter data for this key
        RateLimiter::clear($key);

        // Number of attempts to make
        $attemptCount = (int) $this->option('attempts');

        $this->info("Rate limit: {$maxAttempts} attempts per {$decaySeconds} seconds");
        $this->info("Making {$attemptCount} attempts...");

        // Make multiple attempts to test rate limiting
        for ($i = 1; $i <= $attemptCount; $i++) {
            $this->output->write("Attempt {$i}: ");

            // Check if the action can be performed
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $secondsLeft = RateLimiter::availableIn($key);
                $this->error("Rate limit exceeded! Try again in {$secondsLeft} seconds.");
            } else {
                // Perform the rate-limited action (and increment the counter)
                RateLimiter::hit($key, $decaySeconds);
                $attemptsLeft = $maxAttempts - RateLimiter::attempts($key);
                $this->info("Success! {$attemptsLeft} attempts left.");
            }

            // Small delay between attempts
            if ($i < $attemptCount) {
                usleep(500000); // 0.5 seconds
            }
        }

        // Show current rate limiter status
        $this->newLine();
        $this->info('Rate limiter status:');
        $this->info('- Total attempts: '.RateLimiter::attempts($key));
        $this->info('- Remaining: '.max(0, $maxAttempts - RateLimiter::attempts($key)));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $secondsLeft = RateLimiter::availableIn($key);
            $this->info("- Reset in: {$secondsLeft} seconds");
        }

        $this->newLine();
        $this->info('Rate limiting test completed!');

        return Command::SUCCESS;
    }
}
