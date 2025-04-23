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

# Run PHPStan
./vendor/bin/sail php ./vendor/bin/phpstan analyse --no-progress

echo "✅ Static analysis completed!"
