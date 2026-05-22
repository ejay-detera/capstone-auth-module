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

  # Seed only if the users table is empty (idempotent — won't overwrite real data)
  USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
  if [ "${USER_COUNT}" = "0" ] || [ -z "${USER_COUNT}" ]; then
    echo "No users found — seeding database..."
    php artisan db:seed --force
  else
    echo "Database already has ${USER_COUNT} user(s) — skipping seed."
  fi

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
