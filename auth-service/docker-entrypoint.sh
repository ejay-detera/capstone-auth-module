#!/bin/sh
set -e

# Use environment variables with fallbacks
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}

# Wait for database to be ready
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
until nc -z "$DB_HOST" "$DB_PORT" > /dev/null 2>&1; do
  echo "Database (${DB_HOST}) is not available yet - sleeping"
  sleep 1
done
echo "Database is ready!"

if [ "${SKIP_MIGRATIONS}" != "true" ]; then
  # Discover packages
  echo "Discovering packages..."
  php artisan package:discover --ansi

  # Run migrations
  echo "Running migrations..."
  php artisan migrate --force

  # Clear cache after migrations
  echo "Clearing cache..."
  php artisan cache:clear
  php artisan optimize:clear
else
  echo "Skipping initialization tasks (SKIP_MIGRATIONS is set to true)..."
fi

# Start the application
echo "Starting application..."
exec "$@"
