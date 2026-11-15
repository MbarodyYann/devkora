#!/usr/bin/env bash
# Démarrage du conteneur : port de Nginx, choix de la base, caches, puis le site démarre tout de suite.
# Si la base managée (DB_*) ne répond pas, une base PostgreSQL locale au conteneur prend le relais :
# la démo s'y sème au premier lancement (ses données repartent de zéro à chaque redéploiement).
set -Eeuo pipefail
cd /var/www/devkora

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/conf.d/devkora.conf.template > /etc/nginx/conf.d/devkora.conf

run() { su -s /bin/bash www-data -c "$*"; }

managed=false
for attempt in 1 2 3; do
    if php docker/db-check.php; then managed=true; break; fi
    echo "Base managée injoignable (essai $attempt/3)."
    sleep 3
done

if [ "$managed" = false ]; then
    echo "Base locale de secours."
    PGBIN=$(ls -d /usr/lib/postgresql/*/bin | head -n1)
    PGDATA=/var/lib/postgresql/devkora
    if [ ! -s "$PGDATA/PG_VERSION" ]; then
        install -d -o postgres -g postgres "$PGDATA"
        su postgres -c "$PGBIN/initdb -D $PGDATA -E UTF8 --locale=C.UTF-8 -A trust" >/dev/null
    fi
    su postgres -c "$PGBIN/pg_ctl -D $PGDATA -l /tmp/postgres.log -o '-c listen_addresses=127.0.0.1 -c shared_buffers=32MB' -w start" >/dev/null
    su postgres -c "psql -tAc \"select 1 from pg_roles where rolname='devkora'\" | grep -q 1 || psql -c \"create role devkora login password 'devkora' superuser\"" >/dev/null
    su postgres -c "psql -tAc \"select 1 from pg_database where datname='devkora'\" | grep -q 1 || createdb -O devkora devkora" >/dev/null
    export DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=devkora DB_USERNAME=devkora DB_PASSWORD=devkora
fi

run "php artisan package:discover --ansi"
run "php artisan storage:link --force" || true
run "DB_HOST=$DB_HOST DB_PORT=$DB_PORT DB_DATABASE=$DB_DATABASE DB_USERNAME=$DB_USERNAME DB_PASSWORD='$DB_PASSWORD' php artisan optimize"

(
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
