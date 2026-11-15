#!/usr/bin/env bash
# Démarrage du conteneur : port de Nginx, caches, puis le site démarre tout de suite.
# Les migrations et la démo du premier lancement tournent en arrière-plan, avec des essais répétés
# tant que la base n'est pas joignable : un démarrage lent de la base ne fait plus redémarrer le conteneur.
set -Eeuo pipefail
cd /var/www/devkora

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/conf.d/devkora.conf.template > /etc/nginx/conf.d/devkora.conf

run() { su -s /bin/bash www-data -c "$*"; }

run "php artisan package:discover --ansi"
run "php artisan storage:link --force" || true
run "php artisan optimize"

(
    for attempt in $(seq 1 30); do
        if run "php artisan migrate --force"; then
            # Base vide (premier lancement) : on sème la démo.
            if [ "${APP_DEMO:-false}" = "true" ] && [ "$(run "php docker/users-count.php")" = "0" ]; then
                run "php artisan demo:reset"
            fi
            echo "Base prête."
            exit 0
        fi
        echo "Base injoignable (essai $attempt/30), nouvel essai dans 10 s."
        sleep 10
    done
    echo "Base toujours injoignable : vérifie DB_HOST, DB_PORT et les identifiants." >&2
) &

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/devkora.conf
