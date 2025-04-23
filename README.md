# Day in Review

<p align="center">
<a href="https://github.com/dodwmd/dayinreview/actions/workflows/ci-cd.yml"><img src="https://github.com/dodwmd/dayinreview/actions/workflows/ci-cd.yml/badge.svg" alt="CI/CD Status"></a>
<a href="https://github.com/dodwmd/dayinreview/actions/workflows/quality-checks.yml"><img src="https://github.com/dodwmd/dayinreview/actions/workflows/quality-checks.yml/badge.svg" alt="Quality Checks"></a>
<a href="https://github.com/dodwmd/dayinreview/actions/workflows/tests.yml"><img src="https://github.com/dodwmd/dayinreview/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Day in Review

Day in Review is a web application that aggregates popular content from Reddit and YouTube into personalized playlists. The application helps users discover trending videos and content from their favorite subreddits and YouTube channels in one convenient place.

## Features

- **Content Aggregation**: Automatically collects popular videos from Reddit and YouTube.
- **Personalized Playlists**: Create custom playlists based on your interests.
- **Trend Detection**: Identifies trending videos based on view and engagement metrics.
- **Subscription Management**: Follow your favorite subreddits and YouTube channels.
- **Caching System**: Optimized performance with Redis-based caching.
- **API Rate Limiting**: Respects API limits for Reddit and YouTube.

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Inertia.js with Vue 3
- **Database**: MySQL
- **Caching**: Redis
- **Container**: Docker with Laravel Sail
- **CI/CD**: GitHub Actions

## Installation

### Prerequisites

- Docker and Docker Compose
- Composer
- Node.js and NPM

### Setup Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/dodwmd/dayinreview.git
   cd dayinreview
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Set up environment:
   ```bash
   cp .env.example .env
   ./vendor/bin/sail up -d
   ./vendor/bin/sail artisan key:generate
   ```

4. Run migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

5. Build frontend assets:
   ```bash
   npm run dev
   ```

## API Configuration

### Reddit API

1. Create a Reddit App at https://www.reddit.com/prefs/apps
2. Add the credentials to your `.env` file:
   ```
   REDDIT_CLIENT_ID=your_client_id
   REDDIT_CLIENT_SECRET=your_client_secret
   REDDIT_USERNAME=your_username
   REDDIT_PASSWORD=your_password
   REDDIT_USER_AGENT="web:com.dayinreview:v1.0.0 (by /u/your_username)"
   ```

### YouTube API

1. Create a project in the Google Developers Console
2. Enable the YouTube Data API v3
3. Create API credentials
4. Add the API key to your `.env` file:
   ```
   YOUTUBE_API_KEY=your_api_key
   ```

## Development

Run the development server:
```bash
./vendor/bin/sail up -d
npm run dev
```

## Testing

Run the test suite:
```bash
./vendor/bin/sail artisan test
```

Run static analysis:
```bash
./vendor/bin/sail composer analyze
```

Run code style checks:
```bash
./vendor/bin/sail composer lint
```

## License

The Day in Review application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
