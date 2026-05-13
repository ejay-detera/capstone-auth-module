#!/bin/sh
set -e

# Wait for database to be ready
echo "Waiting for database..."
until nc -z db 3306; do
  sleep 1
done
echo "Database is ready!"

# Clear cache and discover packages
echo "Discovering packages..."
php artisan package:discover --ansi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Start the application
echo "Starting application..."
exec "$@"
