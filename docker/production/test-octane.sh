#!/usr/bin/env bash
# Test browser tests against the production FrankenPHP Docker image.
# Builds the prod image, starts it, runs browser tests against it.
#
# Local: requires Docker + running Sail (sail up -d)
# CI:    requires Docker + PHP/Playwright on host
#
# Usage: ./docker/production/test-octane.sh
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_DIR"

CONTAINER_NAME="invoicing-octane-test"
IMAGE_NAME="invoicing-test:octane"
PROD_PORT=8001

cleanup() {
    echo "Stopping production test container..."
    docker stop "$CONTAINER_NAME" 2>/dev/null || true
    docker rm "$CONTAINER_NAME" 2>/dev/null || true
}
trap cleanup EXIT

# --- Step 1: Build production image ---
echo "==> Building production Docker image..."
docker build \
    -f docker/production/Dockerfile.frankenphp \
    --target production \
    -t "$IMAGE_NAME" .

# --- Step 2: Start production container on port 8001 ---
echo "==> Starting production container on port $PROD_PORT..."
docker run -d --name "$CONTAINER_NAME" \
    -p "$PROD_PORT:$PROD_PORT" \
    -e APP_KEY=base64:$(openssl rand -base64 32) \
    -e APP_ENV=testing \
    -e APP_URL=http://127.0.0.1:$PROD_PORT \
    -e DB_CONNECTION=sqlite \
    -e "DB_DATABASE=/app/storage/database.sqlite" \
    -e CACHE_STORE=array \
    -e SESSION_DRIVER=array \
    -e QUEUE_CONNECTION=sync \
    -e MAIL_MAILER=array \
    -e TELESCOPE_ENABLED=false \
    -e OCTANE_ENABLED=true \
    -e OCTANE_SERVER=frankenphp \
    -e OCTANE_HOST=0.0.0.0 \
    -e OCTANE_PORT=$PROD_PORT \
    -e OCTANE_WORKERS=2 \
    -e OCTANE_MAX_REQUESTS=500 \
    -e RUN_MIGRATIONS=true \
    "$IMAGE_NAME"

# --- Step 3: Wait for healthy ---
echo "==> Waiting for container to be healthy..."
for i in $(seq 1 30); do
    if curl -sf "http://127.0.0.1:$PROD_PORT/up" > /dev/null 2>&1; then
        echo "Container is ready!"
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "ERROR: Container failed to start within 60 seconds"
        echo "Container logs:"
        docker logs "$CONTAINER_NAME"
        exit 1
    fi
    sleep 2
done

# --- Step 4: Run browser tests ---
echo "==> Running browser tests against production image on :$PROD_PORT..."

if [ -n "$CI" ]; then
    # CI: PHP + Playwright available on host runner
    APP_URL="http://127.0.0.1:$PROD_PORT" php artisan test tests/Browser --compact
else
    # Local: Run via Sail. Playwright inside Sail reaches host via host.docker.internal.
    APP_URL="http://host.docker.internal:$PROD_PORT" \
        vendor/bin/sail php artisan test tests/Browser --compact
fi
