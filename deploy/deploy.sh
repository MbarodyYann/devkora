#!/usr/bin/env bash
#
# Déploiement de DevKora sur le serveur. Idempotent : se relance sans risque, autant de fois que nécessaire.
#
# Usage, en tant qu'utilisateur devkora (jamais root) :
#   sudo -u devkora /var/www/devkora/deploy/deploy.sh [branche]
#
# Étapes : récupération du code, maintenance, dépendances PHP sans les outils de développement,
# build des assets, migrations, caches de Laravel, rechargement de PHP-FPM, fin de maintenance.
# En cas d'échec, le site reste en maintenance : corrige, puis relance le script.

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/devkora}"
BRANCH="${1:-main}"
PHP_BIN="${PHP_BIN:-php8.2}"
FPM_SERVICE="${FPM_SERVICE:-php8.2-fpm}"

log() {
    printf '\n==> %s\n' "$*"
}

fail() {
    printf '\nÉchec : %s\n' "$*" >&2
    exit 1
}

trap 'printf "\nÉchec à la ligne %s : le site reste en maintenance. Corrige, puis relance le script.\n" "$LINENO" >&2' ERR

[[ $EUID -ne 0 ]] || fail "lance ce script en tant que devkora : sudo -u devkora $0"
cd "$APP_DIR" || fail "dossier $APP_DIR introuvable"
[[ -f .env ]] || fail "fichier .env absent : copie .env.example.production en .env et remplis-le (voir le guide)"

log "Récupération de la branche $BRANCH"
# Avant la maintenance : un problème réseau ne coupe pas le site.
git fetch --prune origin "$BRANCH"

log "Mode maintenance"
# Au premier déploiement, vendor/ n'existe pas encore : rien à mettre en maintenance.
if [[ -f vendor/autoload.php ]]; then
    "$PHP_BIN" artisan down --retry=15 --refresh=15 || true
fi

log "Mise à jour du code"
git checkout -q "$BRANCH"
git merge --ff-only "origin/$BRANCH"

log "Dépendances PHP (sans les outils de développement)"
"$PHP_BIN" "$(command -v composer)" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

# Clé de l'application : générée une seule fois, au premier déploiement.
if ! grep -q '^APP_KEY=base64:' .env; then
    log "Génération de la clé de l'application"
    "$PHP_BIN" artisan key:generate --force
fi

log "Assets (Vite)"
# Playwright sert aux tests en local : aucun navigateur à télécharger sur le serveur.
PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm ci --no-audit --no-fund
npm run build

log "Migrations"
"$PHP_BIN" artisan migrate --force

if [[ ! -L public/storage ]]; then
    "$PHP_BIN" artisan storage:link
fi

log "Caches (configuration, routes, vues, événements)"
"$PHP_BIN" artisan optimize

log "Rechargement de PHP-FPM"
# OPcache ne relit pas les fichiers (validate_timestamps=0) : le rechargement charge le nouveau code.
sudo -n systemctl reload "$FPM_SERVICE"

log "Fin de la maintenance"
"$PHP_BIN" artisan up

log "Déploiement terminé : $(git log -1 --format='%h %s')"
