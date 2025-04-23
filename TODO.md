# Day in Review - Project TODO

## Project Overview
"Day in Review" is a web application that aggregates popular content from Reddit and YouTube into personalized playlists for users, eliminating doom scrolling while keeping users informed on trending content.

## Tech Stack

### Backend Framework
- **Laravel 11** - Utilizing modern features including:
  - Folio page-based routing for cleaner organization
  - Laravel Pennant for feature flags (A/B testing different playlist algorithms)
  - Sanctum for API authentication
  - Eloquent ORM with UUID primary keys for better distribution
  - Rate limiting with Redis for API consumption
  - Queue system for scheduled content aggregation

### Frontend
- **Inertia.js** with **Vue 3** - For seamless SPA experience with server-side rendering capabilities
- **Tailwind CSS** - For responsive and clean UI design
- **Headless UI** - For accessible UI components
- **Alpine.js** - For interactive elements requiring minimal JavaScript

### APIs & External Services
- **Reddit API** - For fetching popular posts and subreddit content
- **YouTube Data API v3** - For video data and playlist management
- **YouTube Player API** - For embedded video playback
- **Google OAuth** - For YouTube integration and user authentication

### Database
- **MySQL 8** - Primary database with full-text search capabilities
- **Redis** - For caching, rate limiting, and queue processing

### DevOps & Deployment
- **Docker** - Containerization for development and production
- **Laravel Sail** - Local development environment
- **GitHub Actions** - CI/CD pipeline
  - [x] CI/CD workflow with testing, building, and deployment
  - [x] Terramate workflow for infrastructure management
  - [x] Package publishing to GitHub Container Registry
- **Digital Ocean** - Cloud hosting platform

## Development Tasks

### 1. Project Setup
- [x] Initialize Laravel 11 project
- [x] Configure Docker environment with Laravel Sail
- [x] Set up database migrations
- [x] Configure Redis
- [x] Set up GitHub repository with CI/CD
  - [x] Configure workflows with proper permissions
  - [x] Set up container registry publishing
- [ ] Configure development environments (local, staging, production)

### 2. API Integrations
- [x] Set up Reddit API integration
  - [x] Create RedditService class in app/Services/Reddit/
  - [x] Implement methods for fetching popular posts
  - [x] Add caching layer to respect API rate limits
  - [x] Create tests for Reddit API integration

- [x] Set up YouTube API integration
  - [x] Create YouTubeService class in app/Services/YouTube/
  - [x] Implement methods for video lookup and playlist management
  - [x] Add caching layer to respect API rate limits
  - [x] Create tests for YouTube API integration

### 3. Core Content Aggregation
- [x] Implement content fetching services
  - [x] Create ContentAggregationService in app/Services/Content/
  - [x] Implement Reddit post extraction and filtering
  - [x] Implement YouTube video extraction from Reddit posts
  - [x] Create scheduled task for daily content updates using Laravel's task scheduling

- [x] Set up content repositories
  - [x] Create ContentRepository in app/Repositories/
  - [x] Implement caching strategies using Redis
  - [x] Create database models and relationships

### 4. User Authentication System
- [ ] Implement user authentication
  - [ ] Set up Laravel Breeze/Fortify for authentication scaffolding
  - [ ] Implement Google OAuth for YouTube integration
  - [ ] Create user profile management
  - [ ] Implement user settings and preferences

- [ ] Set up subscription management
  - [ ] Create SubscriptionService in app/Services/Subscription/
  - [ ] Implement methods for managing Reddit channel subscriptions
  - [ ] Implement methods for managing YouTube channel subscriptions
  - [ ] Create database models for subscriptions

### 5. Playlist Generation
- [ ] Implement playlist engine
  - [ ] Create PlaylistService in app/Services/Playlist/
  - [ ] Implement algorithms for generating personalized playlists
  - [ ] Create separation between trending and subscription content
  - [ ] Implement YouTube playlist API integration

- [ ] Set up playlist repositories
  - [ ] Create PlaylistRepository in app/Repositories/
  - [ ] Implement caching strategies for playlists
  - [ ] Create database models for playlists

### 6. Frontend Development
- [ ] Set up Inertia.js with Vue 3
  - [ ] Configure Inertia middleware
  - [ ] Set up Vue components structure
  - [ ] Implement Tailwind CSS

- [ ] Develop UI components
  - [ ] Create responsive layout with mobile-first approach
  - [ ] Implement user dashboard
  - [ ] Create subscription management interface
  - [ ] Build playlist viewer with YouTube player integration
  - [ ] Implement user settings interface

### 7. Testing & QA
- [ ] Implement comprehensive testing
  - [ ] Set up Pest testing framework
  - [ ] Create feature tests for core functionality
  - [ ] Implement API tests for external integrations
  - [ ] Set up browser testing with Laravel Dusk

- [ ] Performance optimization
  - [ ] Implement query caching
  - [ ] Optimize database queries
  - [ ] Set up proper indexing
  - [ ] Implement lazy loading for collections

### 8. Deployment & Launch
- [ ] Finalize deployment pipeline
  - [ ] Configure production environment
  - [ ] Set up monitoring and logging
  - [ ] Implement backup strategies
  - [ ] Configure SSL and security measures

- [ ] Launch preparations
  - [ ] Create user documentation
  - [ ] Implement analytics
  - [ ] Set up error tracking
  - [ ] Create launch checklist

## Implementation Details

### Content Aggregation Service
```php
namespace App\Services\Content;

class ContentAggregationService
{
    public function __construct(
        private readonly RedditService $redditService,
        private readonly YouTubeService $youtubeService,
        private readonly ContentRepository $contentRepository
    ) {}

    public function aggregateDailyContent(): void
    {
        // Fetch popular Reddit posts
        $popularPosts = $this->redditService->getPopularPosts();
        
        // Extract YouTube videos
        $youtubeVideos = $this->extractYouTubeVideos($popularPosts);
        
        // Store in repository with caching
        $this->contentRepository->storeDailyContent($youtubeVideos);
    }
}
```

### Playlist Generation Service
```php
namespace App\Services\Playlist;

class PlaylistService
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly YouTubeService $youtubeService
    ) {}

    public function generateUserPlaylist(User $user): Playlist
    {
        // Get trending videos from user's subscribed subreddits
        $trendingVideos = $this->contentRepository->getTrendingVideosForUser($user);
        
        // Get videos from user's subscribed YouTube channels
        $subscriptionVideos = $this->youtubeService->getVideosFromUserSubscriptions($user);
        
        // Create playlist with clear separation
        return $this->createPlaylist($user, $trendingVideos, $subscriptionVideos);
    }
}
```

### Data Models

#### User Model
```php
namespace App\Models;

class User extends Authenticatable
{
    use HasApiTokens, HasUuids, HasFactory, Notifiable;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'youtube_token',
        'reddit_token',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
        'youtube_token',
        'reddit_token',
    ];
    
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
    
    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }
}
```

#### Subscription Model
```php
namespace App\Models;

class Subscription extends Model
{
    use HasUuids, HasFactory;
    
    protected $fillable = [
        'user_id',
        'platform', // 'reddit' or 'youtube'
        'channel_id',
        'channel_name',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Playlist Model
```php
namespace App\Models;

class Playlist extends Model
{
    use HasUuids, HasFactory;
    
    protected $fillable = [
        'user_id',
        'youtube_playlist_id',
        'date',
        'is_public',
    ];
    
    protected $casts = [
        'date' => 'date',
        'is_public' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function videos()
    {
        return $this->belongsToMany(Video::class);
    }
}
```

## API Rate Limit Considerations
- Reddit API: 60 requests per minute
- YouTube API: 10,000 units per day (most operations cost 1-100 units)

## Performance Optimization Strategy
1. Implement aggressive caching for API responses
2. Use queue workers for content aggregation
3. Implement database indexing for frequent queries
4. Use lazy loading for collections
5. Implement Redis for caching and queues

## Security Measures
1. Store API keys in .env file (never in code)
2. Implement sanctum for API authentication
3. Use HTTPS for all connections
4. Implement rate limiting for API endpoints
5. Store user tokens securely
6. Keep user identities private while playlists remain public

## Future Enhancements
1. Implement recommendation engine based on user preferences
2. Add support for additional content sources (Twitter, TikTok)
3. Implement content categorization and tagging
4. Add social sharing features
5. Implement push notifications for new content
