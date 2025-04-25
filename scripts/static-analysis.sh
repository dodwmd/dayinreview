#!/bin/bash
set -e

echo "🔍 Running PHPStan static analysis..."

# Create PHPStan config if it doesn't exist
if [ ! -f ./phpstan.neon ]; then
    echo "Creating default PHPStan configuration..."
    cp ./scripts/phpstan.neon.dist ./phpstan.neon || echo "parameters:
    level: 5
    paths:
        - app
        - config
        - database
        - routes
    excludePaths:
        - vendor
        - node_modules
        - bootstrap/cache
    checkMissingIterableValueType: false" > ./phpstan.neon
fi

# Check if running in CI environment
if [ -n "$CI" ]; then
    # In CI, run PHPStan directly without Sail
    ./vendor/bin/phpstan analyse --no-progress
else
    # Make sure Sail is running for local environment
    if ! ./vendor/bin/sail ps | grep -q 'laravel'; then
        echo "Sail is not running."
        echo "You may start Sail using the following commands: './vendor/bin/sail up' or './vendor/bin/sail up -d'"
        exit 1
    fi
    # In local environment, run through Sail
    ./vendor/bin/sail php ./vendor/bin/phpstan analyse --no-progress
fi

echo "✅ PHPStan analysis completed!"

# Run Psalm
echo "🔍 Running Psalm static analysis..."

if [ -n "$CI" ]; then
    # In CI, run Psalm directly without Sail
    ./vendor/bin/psalm --no-progress --no-cache
else
    # In local environment, run through Sail
    ./vendor/bin/sail php ./vendor/bin/psalm --no-progress --no-cache
fi

echo "✅ Psalm analysis completed!"

echo "✅ All static analysis completed successfully!"
