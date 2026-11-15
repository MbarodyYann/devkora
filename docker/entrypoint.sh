#!/usr/bin/env bash
# Démarrage du conteneur : port de Nginx, caches, migrations, données de démo au premier lancement.
set -Eeuo pipefail
cd /var/www/devkora

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/conf.d/devkora.conf.template > /etc/nginx/conf.d/devkora.conf

run() { su -s /bin/bash www-data -c "$*"; }

run "php artisan package:discover --ansi"
run "php artisan storage:link --force" || true
run "php artisan optimize"
run "php artisan migrate --force"

# Base vide (premier lancement) : on sème la démo.
if [ "${APP_DEMO:-false}" = "true" ] && [ "$(run "php docker/users-count.php")" = "0" ]; then
    run "php artisan demo:reset"
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/devkora.conf
