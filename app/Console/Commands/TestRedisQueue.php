<?php

namespace App\Console\Commands;

use App\Jobs\TestJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class TestRedisQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-redis-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Redis queue functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Redis queue functionality...');

        // Clear any previous test data
        Cache::forget('last_test_job_processed');

        // Create a test message
        $testMessage = 'Test job dispatched at '.now()->toDateTimeString();

        // Dispatch the job
        $this->info('Dispatching test job with message: '.$testMessage);
        TestJob::dispatch($testMessage);

        // Display queue info
        $this->info('Queue driver: '.config('queue.default'));
        $this->info('Redis connection: '.config('database.redis.default.host').':'.
                    config('database.redis.default.port'));

        $this->info('Job dispatched successfully!');
        $this->info('To process the job, run: php artisan queue:work');

        // Check if there's a queue worker process already running
        $this->info('Checking for queue workers...');
        exec('ps aux | grep "queue:work" | grep -v grep', $output);

        if (count($output) > 0) {
            $this->info('Queue worker is running. The job should be processed shortly.');

            // Wait for job to be processed (up to 10 seconds)
            $this->info('Waiting for job to be processed...');
            $processed = false;

            for ($i = 0; $i < 10; $i++) {
                sleep(1);
                $lastProcessed = Cache::get('last_test_job_processed');

                if ($lastProcessed) {
                    $this->info('Job processed successfully!');
                    $this->info('Result: '.$lastProcessed);
                    $processed = true;
                    break;
                }

                $this->output->write('.');
            }

            if (! $processed) {
                $this->warn('Job not processed within timeout period. You may need to check the queue worker.');
            }
        } else {
            $this->warn('No queue worker running. Start a queue worker with: php artisan queue:work');
        }

        return Command::SUCCESS;
    }
}
