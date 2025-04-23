#!/bin/bash
set -e

echo "🌙 Running Laravel Dusk browser tests..."

# Prepare chrome driver
./vendor/bin/sail php artisan dusk:chrome-driver --detect

# Start chrome driver
./vendor/bin/sail php artisan dusk:chrome-driver-start

# Wait for chrome driver to be ready
sleep 2

# Run Dusk tests
./vendor/bin/sail php artisan dusk

# Stop chrome driver
./vendor/bin/sail php artisan dusk:chrome-driver-stop

echo "✅ Dusk tests completed!"
