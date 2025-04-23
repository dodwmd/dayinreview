#!/bin/bash
set -e

echo "🏗️ Building Day in Review application for production..."

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear and cache routes
echo "🧹 Optimizing Laravel application..."
php artisan optimize:clear
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install and build frontend assets
if [ -f package.json ]; then
    echo "📦 Installing Node.js dependencies..."
    npm ci --production

    echo "🔨 Building frontend assets..."
    npm run build
fi

echo "✅ Build completed successfully!"
