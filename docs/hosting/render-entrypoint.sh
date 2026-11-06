#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
cd /var/www/html
[[ ${PORT:-10000} = 10000 ]] || { echo 'Set Render PORT=10000 for the included Nginx configuration.' >&2; exit 1; }
: "${APP_KEY:?Set a stable Laravel APP_KEY in Render Environment}"
: "${APP_URL:?Set the HTTPS Render application URL}"
: "${DB_HOST:?Set the hosted MySQL host}"
: "${DB_DATABASE:?Set the hosted MySQL database name}"
: "${DB_USERNAME:?Set the hosted MySQL database user}"
: "${DB_PASSWORD:?Set the hosted MySQL database password}"
: "${MYSQL_ATTR_SSL_CA:?Set MYSQL_ATTR_SSL_CA to your provider CA secret file path}"
[[ -r $MYSQL_ATTR_SSL_CA ]] || { echo 'MySQL CA secret file is missing or unreadable.' >&2; exit 1; }
[[ $APP_URL = https://* && ${APP_DEBUG:-false} = false ]] || { echo 'HTTPS URL and APP_DEBUG=false are required.' >&2; exit 1; }

# Render Free has no interactive shell/pre-deploy job: run idempotent migrations
# at startup and fail closed before serving if they fail. Never reset/seed demos.
php artisan config:clear
php artisan migrate --force --no-interaction
php artisan staff:bootstrap-admin --no-interaction
# Do not retain the one-time bootstrap password in the FPM environment/cache.
unset INITIAL_ADMIN_PASSWORD
php artisan optimize
php artisan ops:heartbeat
chown -R www-data:www-data storage bootstrap/cache
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/handh.conf
