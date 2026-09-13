#!/usr/bin/env bash
# ParbaTo deployment helper — non-destructive.
# Requires: git, composer, npm, php, configured .env
set -euo pipefail

APP_DIR="${APP_DIR:-$(pwd)}"
cd "$APP_DIR"

if [[ ! -f artisan ]]; then
  echo "Run from the ParbaTo project root (artisan not found)." >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo ".env is missing. Copy .env.example and configure secrets first." >&2
  exit 1
fi

php artisan --version >/dev/null

MAINTENANCE_ON=0
cleanup() {
  if [[ "$MAINTENANCE_ON" -eq 1 ]]; then
    echo "==> Restoring application (trap)"
    php artisan up || true
  fi
}
trap cleanup EXIT

echo "==> Entering maintenance mode"
php artisan down || true
MAINTENANCE_ON=1

echo "==> Pulling latest code (fast-forward only)"
git pull --ff-only origin main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Building frontend assets"
if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi
npm run build

echo "==> Running migrations (no seed, no refresh)"
php artisan migrate --force

echo "==> Optimizing"
php artisan optimize

echo "==> Restarting queue workers (Supervisor)"
if command -v supervisorctl >/dev/null 2>&1; then
  sudo supervisorctl restart parbato-worker:* || true
else
  echo "supervisorctl not found — restart your queue worker manually."
fi

echo "==> Health check"
curl -fsS "${APP_URL:-http://127.0.0.1:8000}/up" >/dev/null || echo "Warning: /up health check failed (verify APP_URL)."

echo "==> Leaving maintenance mode"
php artisan up
MAINTENANCE_ON=0
trap - EXIT

echo "Deploy finished."
