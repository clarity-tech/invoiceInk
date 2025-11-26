#!/bin/bash

# Production entrypoint script for Laravel with Nginx + PHP-FPM
set -e

echo "Starting Laravel production container with Nginx + PHP-FPM..."

# Wait for database to be ready if DB_HOST is set
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-5432}..."
    timeout=60
    while ! nc -z "$DB_HOST" "${DB_PORT:-5432}" && [ $timeout -gt 0 ]; do
        sleep 1
        timeout=$((timeout - 1))
    done
    
    if [ $timeout -eq 0 ]; then
        echo "ERROR: Database connection timeout"
        exit 1
    fi
    echo "Database is ready!"
fi

# Wait for Redis to be ready if REDIS_HOST is set
if [ -n "$REDIS_HOST" ]; then
    echo "Waiting for Redis at $REDIS_HOST:${REDIS_PORT:-6379}..."
    timeout=30
    while ! nc -z "$REDIS_HOST" "${REDIS_PORT:-6379}" && [ $timeout -gt 0 ]; do
        sleep 1
        timeout=$((timeout - 1))
    done
    
    if [ $timeout -eq 0 ]; then
        echo "WARNING: Redis connection timeout, continuing anyway..."
    else
        echo "Redis is ready!"
    fi
fi

# Ensure proper permissions
echo "Setting up file permissions..."
chown -R www-data:www-data /var/www/html
find /var/www/html -type f -exec chmod 644 {} \;
find /var/www/html -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run Laravel optimizations
echo "Running Laravel optimizations..."

# Discover packages first (skipped during build)
echo "Discovering packages..."
composer run-script post-autoload-dump

if [ "$APP_ENV" = "production" ]; then
    # Clear any existing caches first
    php artisan optimize:clear

    # Cache configurations in production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Run optimizations
    php artisan optimize
else
    # Clear caches in non-production environments
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan event:clear
fi

# Run database migrations if requested
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Run database seeding if requested
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force --no-interaction
fi

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in the background
echo "Starting Nginx..."
nginx -g "daemon off;" &

echo "Services started. Container is ready!"

# Execute the provided command or start supervisor
exec "$@"