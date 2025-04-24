#!/bin/bash
set -e

echo "🌙 Running Laravel Dusk browser tests..."

# Ensure Sail is up and running with all needed services
./vendor/bin/sail up -d

# Install Chrome Driver through Sail
./vendor/bin/sail php artisan dusk:chrome-driver --detect

# Check if migrations need to be run 
echo "Checking database status..."
MIGRATIONS_NEEDED=$(./vendor/bin/sail php artisan migrate:status --env=dusk.local | grep -c "No" || true)
if [ "$MIGRATIONS_NEEDED" -gt 0 ]; then
  echo "Running missing migrations..."
  ./vendor/bin/sail php artisan migrate --env=dusk.local
else
  echo "Migrations already up to date."
fi

# Run Dusk tests through Sail to ensure proper network connectivity
./vendor/bin/sail php artisan dusk --env=dusk.local