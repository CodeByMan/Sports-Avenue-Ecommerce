#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

command -v composer >/dev/null || { echo "Composer 2.8 or newer is required." >&2; exit 1; }
command -v php >/dev/null || { echo "PHP is required." >&2; exit 1; }
command -v npm >/dev/null || { echo "NPM is required." >&2; exit 1; }

COMPOSER_VERSION="$(composer --version --no-ansi | awk '{print $3}')"
php -r 'exit(version_compare($argv[1], "2.8.0", ">=") ? 0 : 1);' "$COMPOSER_VERSION" || {
    echo "Composer 2.8 or newer is required; found $COMPOSER_VERSION." >&2
    exit 1
}

VERIFY_DB="$ROOT/database/release-verification.sqlite"
cleanup() {
    rm -f "$VERIFY_DB"
    php artisan config:clear >/dev/null 2>&1 || true
    php artisan event:clear >/dev/null 2>&1 || true
}
trap cleanup EXIT

composer validate --strict
composer install --no-interaction --prefer-dist
composer check-platform-reqs
composer audit --locked --abandoned=report
php artisan about

: > "$VERIFY_DB"
export APP_ENV=testing
export APP_DEBUG=false
export APP_KEY="$(php artisan key:generate --show --no-ansi)"
export DB_CONNECTION=sqlite
export DB_DATABASE="$VERIFY_DB"
export DB_FOREIGN_KEYS=true
export CACHE_STORE=array
export SESSION_DRIVER=array
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array
export ALLOW_DEMO_SEEDING=true
export MIDTRANS_SERVER_KEY=release-verification-server-key
export MIDTRANS_CLIENT_KEY=release-verification-client-key

php artisan migrate:fresh --seed --force
vendor/bin/pint --test
vendor/bin/phpunit --colors=never --do-not-cache-result
npm ci
npm audit --audit-level=moderate
npm run build
php artisan route:list
php artisan config:cache
php artisan event:cache
php artisan config:clear
php artisan event:clear

echo "Release verification completed successfully against disposable SQLite database: $VERIFY_DB"
