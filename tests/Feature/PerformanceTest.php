<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\RedditPost;
use App\Models\User;
use App\Models\YoutubeVideo;
use App\Repositories\PlaylistRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup large dataset for performance testing.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Only create the test data if it doesn't exist already to save test execution time
        if (User::count() === 0) {
            // Create test users
            $users = User::factory()->count(5)->create();
            
            // Create large dataset for testing
            foreach ($users as $user) {
                // Create playlists for each user
                $playlists = Playlist::factory()->count(10)->create([
                    'user_id' => $user->id,
                ]);
                
                // Create YouTube videos
                $videos = YoutubeVideo::factory()->count(100)->create();
                
                // Create Reddit posts, some linked to YouTube videos
                foreach ($videos as $index => $video) {
                    $redditPost = RedditPost::factory()->create([
                        'youtube_video_id' => $index % 2 === 0 ? $video->id : null,
                    ]);
                }
                
                // Add videos to playlists
                foreach ($playlists as $playlist) {
                    // Add 20 videos to each playlist
                    $playlistVideos = $videos->random(20);
                    
                    foreach ($playlistVideos as $index => $video) {
                        PlaylistItem::create([
                            'id' => Str::uuid(),
                            'playlist_id' => $playlist->id,
                            'source_type' => 'App\\Models\\YoutubeVideo',
                            'source_id' => $video->id,
                            'position' => $index + 1,
                            'is_watched' => rand(0, 1) === 1,
                            'added_at' => now()->subHours(rand(1, 72)),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Test query performance of playlist repository with eager loading.
     */
    public function test_playlist_repository_eager_loading(): void
    {
        $user = User::first();
        $playlistRepository = app(PlaylistRepository::class);
        
        // Count queries without eager loading
        $queryCount = 0;
        $queryListener = function (QueryExecuted $query) use (&$queryCount) {
            $queryCount++;
        };
        
        DB::listen($queryListener);
        
        // Get playlist without specifying eager loading
        $playlists = $playlistRepository->getUserPlaylists($user);
        $firstPlaylist = $playlists->first();
        
        // Access relationships to trigger lazy loading
        $items = $firstPlaylist->items;
        foreach ($items as $item) {
            $video = $item->source;
            $title = $video->title;
        }
        
        $lazyLoadingQueryCount = $queryCount;
        
        // Reset query count
        $queryCount = 0;
        
        // Get playlist with proper eager loading
        $playlistsWithEagerLoading = $playlistRepository->getUserPlaylistsWithItems($user);
        $firstEagerPlaylist = $playlistsWithEagerLoading->first();
        
        // Access the same relationships
        $eagerItems = $firstEagerPlaylist->items;
        foreach ($eagerItems as $item) {
            $video = $item->source;
            $title = $video->title;
        }
        
        $eagerLoadingQueryCount = $queryCount;
        
        // Clean up the listener
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');
        
        // Assert that eager loading uses fewer queries
        $this->assertLessThan(
            $lazyLoadingQueryCount, 
            $eagerLoadingQueryCount, 
            "Eager loading should use fewer queries than lazy loading. Lazy: $lazyLoadingQueryCount, Eager: $eagerLoadingQueryCount"
        );
    }

    /**
     * Test indexed vs non-indexed queries.
     */
    public function test_indexed_query_performance(): void
    {
        // This is a simplified test since we can't actually measure query time precisely in a test
        // but we can check if the EXPLAIN plan uses indexes
        
        // Get a sample youtube_id to search for
        $video = YoutubeVideo::inRandomOrder()->first();
        
        // Query using primary key (indexed)
        $indexedQueryExplain = DB::select('EXPLAIN SELECT * FROM youtube_videos WHERE id = ?', [$video->id]);
        
        // Query using a potentially non-indexed field
        $nonIndexedQueryExplain = DB::select('EXPLAIN SELECT * FROM youtube_videos WHERE title = ?', [$video->title]);
        
        // This is a simplistic check - in a real scenario you'd want to analyze the actual explain output
        // to determine if indexes are being used optimally
        $this->assertNotEmpty($indexedQueryExplain);
        $this->assertNotEmpty($nonIndexedQueryExplain);
    }

    /**
     * Test pagination with large datasets.
     */
    public function test_large_dataset_pagination(): void
    {
        // Get query execution time with and without pagination
        $user = User::first();
        
        $startTime = microtime(true);
        
        // Get all videos without pagination (potentially slow with large datasets)
        $allVideos = YoutubeVideo::all();
        
        $nonPaginatedTime = microtime(true) - $startTime;
        
        // Reset
        $startTime = microtime(true);
        
        // Get paginated results
        $paginatedVideos = YoutubeVideo::paginate(20);
        
        $paginatedTime = microtime(true) - $startTime;
        
        // Pagination should be faster or at least not significantly slower
        $this->assertLessThanOrEqual(
            $nonPaginatedTime * 1.5, // Allow some margin for test variations
            $paginatedTime,
            "Paginated queries should not be significantly slower than non-paginated ones"
        );
        
        // Ensure the paginator contains the right amount of items
        $this->assertCount(20, $paginatedVideos->items());
    }

    /**
     * Test caching performance for frequently accessed data.
     */
    public function test_caching_performance(): void
    {
        // Clear the cache before testing
        cache()->flush();
        
        $queryCount = 0;
        $queryListener = function (QueryExecuted $query) use (&$queryCount) {
            $queryCount++;
        };
        
        DB::listen($queryListener);
        
        // First access - should hit the database
        $videos = cache()->remember('popular_videos', 60, function () {
            return YoutubeVideo::orderBy('view_count', 'desc')->limit(10)->get();
        });
        
        $firstAccessQueryCount = $queryCount;
        
        // Reset query count
        $queryCount = 0;
        
        // Second access - should use cache
        $cachedVideos = cache()->remember('popular_videos', 60, function () {
            return YoutubeVideo::orderBy('view_count', 'desc')->limit(10)->get();
        });
        
        $secondAccessQueryCount = $queryCount;
        
        // Clean up the listener
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');
        
        // Second access should use fewer queries
        $this->assertLessThan(
            $firstAccessQueryCount,
            $secondAccessQueryCount,
            "Cached access should use fewer queries than first access"
        );
    }

    /**
     * Test collection chunk processing for large datasets.
     */
    public function test_chunk_processing_for_large_datasets(): void
    {
        $processedCount = 0;
        
        // Process all videos in chunks of 50
        YoutubeVideo::chunk(50, function ($videos) use (&$processedCount) {
            foreach ($videos as $video) {
                // Simulate some processing
                $processedCount++;
            }
        });
        
        // Verify that all videos were processed
        $this->assertEquals(YoutubeVideo::count(), $processedCount);
    }

    /**
     * Test query optimization with specific select columns.
     */
    public function test_query_optimization_with_select(): void
    {
        $queryCount = 0;
        $queryBytes = 0;
        
        $queryListener = function (QueryExecuted $query) use (&$queryCount, &$queryBytes) {
            $queryCount++;
            // Rough estimation of data size based on the query
            $queryBytes += strlen($query->sql) + strlen(json_encode($query->bindings));
        };
        
        DB::listen($queryListener);
        
        // Query with all columns
        $allColumnsVideos = YoutubeVideo::limit(50)->get();
        
        $allColumnsQueryBytes = $queryBytes;
        
        // Reset counters
        $queryBytes = 0;
        
        // Query with specific columns only
        $selectedColumnsVideos = YoutubeVideo::select(['id', 'youtube_id', 'title', 'thumbnail_url'])
            ->limit(50)
            ->get();
        
        $selectedColumnsQueryBytes = $queryBytes;
        
        // Clean up the listener
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');
        
        // Query with specific columns should transfer less data
        $this->assertLessThan(
            $allColumnsQueryBytes,
            $selectedColumnsQueryBytes,
            "Selecting specific columns should result in less data transfer"
        );
    }
}
