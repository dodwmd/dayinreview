<?php

namespace App\Console\Commands;

use App\Services\Content\ContentAggregationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AggregateContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:aggregate-content
                            {--timeframe=day : Timeframe for content (hour, day, week, month, year, all)}
                            {--limit=25 : Number of posts to retrieve per source}
                            {--subreddit=* : Specific subreddits to aggregate from}
                            {--trending : Update trending videos after aggregation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate content from Reddit and YouTube';

    /**
     * The content aggregation service.
     *
     * @var ContentAggregationService
     */
    protected ContentAggregationService $contentService;

    /**
     * Create a new command instance.
     */
    public function __construct(ContentAggregationService $contentService)
    {
        parent::__construct();
        $this->contentService = $contentService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeframe = $this->option('timeframe');
        $limit = (int) $this->option('limit');
        $subreddits = $this->option('subreddit');
        $updateTrending = $this->option('trending');

        $this->info("Starting content aggregation (timeframe: {$timeframe}, limit: {$limit})");
        
        if (!empty($subreddits)) {
            $this->info("Targeting specific subreddits: " . implode(', ', $subreddits));
        } else {
            $this->info("Aggregating from r/popular");
        }

        try {
            // Start the aggregation
            $startTime = microtime(true);
            $stats = $this->contentService->aggregateDailyContent($subreddits, $timeframe, $limit);
            $duration = round(microtime(true) - $startTime, 2);

            // Display stats
            $this->info("Content aggregation completed in {$duration}s");
            $this->info("Processed {$stats['processed_posts']} posts");
            $this->info("Saved {$stats['saved_reddit_posts']} Reddit posts");
            $this->info("Saved {$stats['saved_youtube_videos']} YouTube videos");

            // Check for errors
            if (!empty($stats['errors'])) {
                $this->error("Encountered errors during aggregation:");
                foreach ($stats['errors'] as $error) {
                    $this->error("- {$error}");
                }
            }

            // Update trending videos if requested
            if ($updateTrending) {
                $this->info("Updating trending videos...");
                $trendingCount = $this->contentService->updateTrendingVideos();
                $this->info("Marked {$trendingCount} videos as trending");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Fatal error during content aggregation: {$e->getMessage()}");
            Log::error('Content aggregation command failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
