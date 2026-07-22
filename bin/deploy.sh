#!/usr/bin/env bash

set -Eeuo pipefail

: "${APP_DIR:?Set APP_DIR to the absolute B2B application directory}"

DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
if [ -x /usr/local/bin/php8.2 ]; then
  PHP_BIN="${PHP_BIN:-/usr/local/bin/php8.2}"
else
  PHP_BIN="${PHP_BIN:-php}"
fi

if [ -f "$APP_DIR/storage/tools/composer2" ]; then
  COMPOSER_BIN="${COMPOSER_BIN:-$APP_DIR/storage/tools/composer2}"
else
  COMPOSER_BIN="${COMPOSER_BIN:-composer}"
fi

cd "$APP_DIR"

test -d .git || { echo "Not a Git checkout: $APP_DIR" >&2; exit 1; }
test -f .env || { echo "Missing protected $APP_DIR/.env" >&2; exit 1; }
test "$(stat -c '%a' .env)" = "600" || { echo "$APP_DIR/.env must have mode 600" >&2; exit 1; }
test "$(git branch --show-current)" = "$DEPLOY_BRANCH" || { echo "Expected branch $DEPLOY_BRANCH" >&2; exit 1; }
git diff --quiet && git diff --cached --quiet || { echo "Refusing to deploy over local changes" >&2; exit 1; }

git fetch --prune origin "$DEPLOY_BRANCH"
git merge --ff-only "origin/$DEPLOY_BRANCH"

"$PHP_BIN" "$COMPOSER_BIN" install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --prefer-dist \
  --optimize-autoloader \
  --classmap-authoritative

mkdir -p storage/cache/api storage/logs storage/sessions tmp/cache
chmod 0750 storage storage/cache storage/cache/api storage/logs storage/sessions tmp tmp/cache

"$PHP_BIN" bin/preflight.php

echo "DEPLOY_OK commit=$(git rev-parse --short HEAD)"
