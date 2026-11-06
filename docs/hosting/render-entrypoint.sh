#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
cd /var/www/html
[[ ${PORT:-10000} = 10000 ]] || { echo 'Set Render PORT=10000 for the included Nginx configuration.' >&2; exit 1; }
: "${APP_KEY:?Set a stable Laravel APP_KEY in Render Environment}"
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-}}"
# Replace the explicitly temporary setup placeholder with Render's real URL.
if [[ $APP_URL = https://example.invalid && -n ${RENDER_EXTERNAL_URL:-} ]]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
fi
: "${APP_URL:?Set an HTTPS APP_URL or use the automatic RENDER_EXTERNAL_URL}"
: "${DB_HOST:?Set the hosted MySQL host}"
: "${DB_DATABASE:?Set the hosted MySQL database name}"
: "${DB_USERNAME:?Set the hosted MySQL database user}"
: "${DB_PASSWORD:?Set the hosted MySQL database password}"
if [[ ${DEMO_TLS_ALLOW_SELF_SIGNED:-false} = true ]]; then
    [[ ${APP_ENV:-} = staging ]] || { echo 'Demo TLS is allowed only with APP_ENV=staging.' >&2; exit 1; }
    unset MYSQL_ATTR_SSL_CA
    echo 'Demo-only TLS: encryption required; server identity is not verified.'
else
    : "${MYSQL_ATTR_SSL_CA:?Set MYSQL_ATTR_SSL_CA to your provider CA secret file path}"
    [[ -r $MYSQL_ATTR_SSL_CA ]] || { echo 'MySQL CA secret file is missing or unreadable.' >&2; exit 1; }
fi
[[ $APP_URL = https://* && ${APP_DEBUG:-false} = false ]] || { echo 'HTTPS URL and APP_DEBUG=false are required.' >&2; exit 1; }

# Render Free has no interactive shell/pre-deploy job: run idempotent migrations
# at startup and fail closed before serving if they fail. Never reset/seed demos.
php artisan config:clear
php artisan ops:database --no-interaction
php artisan migrate --force --no-interaction
php artisan staff:bootstrap-admin --no-interaction
# Do not retain the one-time bootstrap password in the FPM environment/cache.
unset INITIAL_ADMIN_PASSWORD
php artisan optimize
php artisan ops:heartbeat
chown -R www-data:www-data storage bootstrap/cache
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/handh.conf
