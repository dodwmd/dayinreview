#!/bin/bash
set -e

echo "🧹 Running Laravel Pint code style fixer..."

# Check if running in CI environment
if [ -n "$CI" ]; then
    # In CI, check code style without fixing
    ./vendor/bin/sail php ./vendor/bin/pint --test
else
    # In local environment, fix code style
    ./vendor/bin/sail php ./vendor/bin/pint
fi

echo "✅ Linting completed!"
