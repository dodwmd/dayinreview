#!/bin/bash
set -e

echo "🧹 Running Laravel Pint code style fixer..."

# Check if running in CI environment
if [ -n "$CI" ]; then
    # In CI, check code style without Sail
    ./vendor/bin/pint --test
else
    # Make sure Sail is running for local environment
    if ! ./vendor/bin/sail ps | grep -q 'laravel'; then
        echo "Sail is not running."
        echo "You may start Sail using the following commands: './vendor/bin/sail up' or './vendor/bin/sail up -d'"
        exit 1
    fi
    # In local environment, fix code style
    ./vendor/bin/sail php ./vendor/bin/pint
fi

echo "✅ Linting completed!"
