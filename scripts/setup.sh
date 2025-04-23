#!/bin/bash
set -e

echo "🚀 Setting up Day in Review application..."

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Install Laravel Pint if not already installed
if [ ! -f ./vendor/bin/pint ]; then
    echo "📦 Installing Laravel Pint..."
    composer require laravel/pint --dev
fi

# Install PHPStan if not already installed
if [ ! -f ./vendor/bin/phpstan ]; then
    echo "📦 Installing PHPStan..."
    composer require phpstan/phpstan --dev
    # Create PHPStan config if it doesn't exist
    if [ ! -f ./phpstan.neon ]; then
        cp ./scripts/phpstan.neon.dist ./phpstan.neon
    fi
fi

# Install Node dependencies
if [ -f package.json ]; then
    echo "📦 Installing Node dependencies..."
    npm install
fi

# Generate application key if not set
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

echo "✅ Setup completed successfully!"
