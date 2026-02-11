#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"
APP_SERVICE="app"
MYSQL_SERVICE="mysql"

echo "==> Pulling latest code from main..."
git pull origin master

echo "==> Building Docker image..."
docker compose -f "$COMPOSE_FILE" build

echo "==> Backing up database..."
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="./backups"
mkdir -p "$BACKUP_DIR"
if docker compose -f "$COMPOSE_FILE" ps "$MYSQL_SERVICE" --status running -q 2>/dev/null | grep -q .; then
    docker compose -f "$COMPOSE_FILE" exec -T "$MYSQL_SERVICE" \
        mysqldump -u"${DB_USERNAME:-clinical}" -p"${DB_PASSWORD}" "${DB_DATABASE:-clinical_history}" \
        | gzip > "${BACKUP_DIR}/backup_${TIMESTAMP}.gz"
    echo "    Backup saved to ${BACKUP_DIR}/backup_${TIMESTAMP}.gz"
else
    echo "    MySQL not running yet, skipping backup."
fi

echo "==> Starting services..."
docker compose -f "$COMPOSE_FILE" up -d

echo "==> Waiting for app to be ready..."
sleep 5

echo "==> Running migrations..."
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan migrate --force

echo "==> Caching configuration..."
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan view:cache
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan event:cache

echo "==> Health check..."
sleep 2
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/up || true)
if [ "$HTTP_STATUS" = "200" ]; then
    echo "    Health check passed (HTTP 200)"
else
    echo "    WARNING: Health check returned HTTP $HTTP_STATUS"
    echo "    Check logs: docker compose -f $COMPOSE_FILE logs app"
fi

echo "==> Deploy complete!"
