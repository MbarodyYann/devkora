#!/usr/bin/env bash
# Démarrage du conteneur. Nginx et PHP-FPM démarrent tout de suite (le contrôle de santé /up répond
# en quelques secondes) ; le choix de la base, les caches et les migrations se font en arrière-plan.
# Si la base managée (DB_*) ne répond pas, la base PostgreSQL locale, déjà semée dans l'image, prend le relais.
set -Eeuo pipefail
cd /var/www/devkora

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/conf.d/devkora.conf.template > /etc/nginx/conf.d/devkora.conf

run() { su -s /bin/bash www-data -c "$*"; }

(
    if php docker/db-check.php; then
        echo "Base managée joignable."
    else
        echo "Base managée injoignable : base locale de secours."
        PGBIN=$(ls -d /usr/lib/postgresql/*/bin | head -n1)
        su postgres -c "$PGBIN/pg_ctl -D /var/lib/postgresql/devkora -l /tmp/postgres.log -o '-c listen_addresses=127.0.0.1 -c shared_buffers=16MB -c max_connections=30' -w start" >/dev/null
        export DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=devkora DB_USERNAME=devkora DB_PASSWORD=devkora
    fi

    run "php artisan package:discover --ansi" || true
    run "php artisan storage:link --force" || true
    run "DB_HOST=$DB_HOST DB_PORT=$DB_PORT DB_DATABASE=$DB_DATABASE DB_USERNAME=$DB_USERNAME DB_PASSWORD='$DB_PASSWORD' php artisan optimize"
    # PHP-FPM relit la configuration mise en cache (OPcache ne revalide pas les fichiers).
    kill -USR2 "$(cat /tmp/php-fpm.pid)" 2>/dev/null || true

    for attempt in $(seq 1 30); do
        if run "php artisan migrate --force"; then
            if [ "${APP_DEMO:-false}" = "true" ] && [ "$(run "php docker/users-count.php")" = "0" ]; then
                run "php artisan demo:reset"
            fi
            echo "Base prête."
            exit 0
        fi
        echo "Base injoignable (essai $attempt/30), nouvel essai dans 10 s."
        sleep 10
    done
) &

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/devkora.conf
