#!/bin/sh

# Production entrypoint script for Laravel with FrankenPHP + Octane
set -e

echo "Starting Laravel production container with FrankenPHP + Octane..."

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

# Generate application key FIRST if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations FIRST (before any cache operations)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Run Laravel optimizations
echo "Running Laravel optimizations..."

# Discover packages first (skipped during build)
echo "Discovering packages..."
# php artisan package:discover --ansi
# composer run-script post-autoload-dump

if [ "$APP_ENV" = "production" ]; then
    # Cache configurations in production (optimize handles config, route, view, event caching)
    php artisan optimize
else
    # Clear caches in non-production environments
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan event:clear
fi

# Run database seeding if requested
if [ "$RUN_SEEDERS" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force --no-interaction
fi

# Start supervisor for background processes (queues, etc.)
if [ "$ENABLE_SUPERVISOR" = "true" ]; then
    echo "Starting supervisor for background processes..."
    supervisord -c /etc/supervisor/conf.d/laravel.conf &
fi

# Check if we should run Octane or regular FrankenPHP
if [ "$OCTANE_ENABLED" = "true" ]; then
    echo "Starting Laravel Octane with FrankenPHP server..."
    exec php artisan octane:frankenphp \
        --host="$OCTANE_HOST" \
        --port="$OCTANE_PORT" \
        --workers="$OCTANE_WORKERS" \
        --max-requests="$OCTANE_MAX_REQUESTS"
else
    echo "Starting FrankenPHP server (per official docs)..."
    # Use standard FrankenPHP command as per official documentation
    exec frankenphp php-server --listen "0.0.0.0:8000"
fi