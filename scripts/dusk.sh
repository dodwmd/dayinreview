#!/bin/bash
set -e

echo " Running Laravel Dusk browser tests..."

# Make sure Chrome driver matches browser version
php artisan dusk:chrome-driver --detect

# Run Dusk tests using the testing environment
php artisan dusk --env=testing