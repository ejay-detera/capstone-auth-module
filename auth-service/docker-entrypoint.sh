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

# Clear cache and discover packages
echo "Clearing cache..."
php artisan cache:clear
php artisan optimize:clear
echo "Discovering packages..."
php artisan package:discover --ansi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Start the application
echo "Starting application..."
exec "$@"
