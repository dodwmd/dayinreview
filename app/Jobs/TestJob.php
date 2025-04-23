<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestJob implements ShouldQueue
{
    use Queueable;

    /**
     * The message to process.
     */
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Log the message
        Log::info('Processing test job: '.$this->message);

        // Update a cache value
        Cache::put('last_test_job_processed', $this->message.' at '.now()->toDateTimeString(), 3600);

        // Simulate some processing time
        sleep(2);

        // Log completion
        Log::info('Test job completed: '.$this->message);
    }
}
