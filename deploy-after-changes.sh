#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"
APP_SERVICE="app"

# Detect docker compose command (v2 plugin vs v1 standalone)
if docker compose version &>/dev/null; then
    DC="docker compose"
elif docker-compose version &>/dev/null; then
    DC="docker-compose"
else
    echo "ERROR: neither 'docker compose' nor 'docker-compose' found"
    exit 1
fi

echo "==> Using: $DC"

echo "==> Pulling latest code..."
git pull origin master

echo "==> Building Docker image..."
$DC -f "$COMPOSE_FILE" build

echo "==> Restarting services..."
$DC -f "$COMPOSE_FILE" up -d

echo "==> Waiting for app to be ready..."
sleep 5

echo "==> Running migrations..."
$DC -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan migrate --force

echo "==> Caching configuration..."
$DC -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan config:cache
$DC -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan route:cache
$DC -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan view:cache
$DC -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan event:cache

echo "==> Health check..."
sleep 2
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/up || true)
if [ "$HTTP_STATUS" = "200" ]; then
    echo "    Health check passed (HTTP 200)"
else
    echo "    WARNING: Health check returned HTTP $HTTP_STATUS"
    echo "    Check logs: $DC -f $COMPOSE_FILE logs app"
fi

echo "==> Deploy complete!"
