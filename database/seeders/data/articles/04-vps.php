<?php

return [
    'author' => 'Abdoul Kaboré', 'category' => 'tutorial', 'age' => 0.72, 'useful' => 11,
    'title' => 'Déployer une application Laravel sur un VPS de 2 Go',
    'tags' => ['Laravel', 'Linux', 'DevOps', 'PostgreSQL'],
    'body' => <<<'MD'
Héberger son application sur un serveur proche de ses utilisateurs, c'est moins de latence, souvent un meilleur prix, et la maîtrise de ses données. Ce guide déploie une application Laravel sur un VPS Ubuntu de 2 Go de RAM, avec Nginx, PHP-FPM, PostgreSQL et HTTPS. C'est la configuration que nous utilisons pour des applications de quelques milliers d'utilisateurs par jour.

## 1. Sécuriser le serveur dès la première minute

Un serveur neuf reçoit des tentatives de connexion SSH dans l'heure. Avant toute chose : un utilisateur dédié, les clés SSH, et plus de mot de passe.

```bash
adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy

# Dans /etc/ssh/sshd_config :
#   PasswordAuthentication no
#   PermitRootLogin no
sudo systemctl restart ssh

sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

Ajoute `fail2ban`, qui bloque les adresses trop insistantes : `sudo apt install fail2ban`.

## 2. Un fichier d'échange (swap)

Avec 2 Go, un `composer install` ou un pic de trafic peuvent épuiser la mémoire, et le noyau tue alors un processus au hasard, souvent PostgreSQL. Un swap de 2 Go évite la catastrophe :

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

## 3. Installer PHP, Nginx et PostgreSQL

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y nginx postgresql postgresql-contrib \
  php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring php8.2-xml \
  php8.2-curl php8.2-zip php8.2-intl php8.2-gd php8.2-bcmath unzip
```

Crée la base et son utilisateur :

```bash
sudo -u postgres createuser --pwprompt monapp
sudo -u postgres createdb --owner=monapp monapp
```

## 4. Régler PHP-FPM pour 2 Go

C'est le réglage le plus important, et celui que tout le monde oublie. Chaque processus PHP qui exécute Laravel consomme 40 à 70 Mo. Avec 2 Go partagés avec PostgreSQL et Nginx, 15 processus est un plafond raisonnable.

Dans `/etc/php/8.2/fpm/pool.d/www.conf` :

```ini
pm = dynamic
pm.max_children = 15
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500
```

`pm.max_requests` recycle les processus régulièrement et évite que la mémoire gonfle avec le temps. Active aussi OPcache dans `/etc/php/8.2/fpm/conf.d/10-opcache.ini` :

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.validate_timestamps=0
```

Avec `validate_timestamps=0`, PHP ne vérifie plus si les fichiers ont changé : il faudra recharger PHP-FPM à chaque déploiement. C'est voulu, et c'est plus rapide.

## 5. Nginx

`/etc/nginx/sites-available/monapp` :

```nginx
server {
    listen 80;
    server_name monapp.sn www.monapp.sn;
    root /var/www/monapp/current/public;
    index index.php;

    client_max_body_size 10M;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

La dernière règle interdit l'accès aux fichiers cachés, dont `.env`. Active le site, puis le HTTPS avec Let's Encrypt :

```bash
sudo ln -s /etc/nginx/sites-available/monapp /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d monapp.sn -d www.monapp.sn
```

Le renouvellement du certificat est ensuite automatique.

## 6. Déployer le code

Un script simple, qui prépare chaque version dans son propre dossier puis bascule un lien symbolique, évite de servir une application à moitié mise à jour :

```bash
#!/usr/bin/env bash
set -euo pipefail

APP=/var/www/monapp
RELEASE="$APP/releases/$(date +%Y%m%d%H%M%S)"

git clone --depth 1 git@github.com:monequipe/monapp.git "$RELEASE"
ln -s "$APP/shared/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage" && ln -s "$APP/shared/storage" "$RELEASE/storage"

cd "$RELEASE"
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize

ln -sfn "$RELEASE" "$APP/current"
sudo systemctl reload php8.2-fpm
```

Les fichiers de build (`npm run build`) sont produits sur ta machine ou dans l'intégration continue, puis envoyés : Node et Vite consomment trop de mémoire pour un petit serveur.

## 7. Les tâches de fond

Le planificateur et la file d'attente tournent avec systemd, qui les relance s'ils s'arrêtent. Pour la file d'attente, `/etc/systemd/system/monapp-queue.service` :

```ini
[Unit]
Description=File d'attente de monapp
After=network.target postgresql.service

[Service]
User=www-data
WorkingDirectory=/var/www/monapp/current
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

Pour le planificateur, une ligne dans la crontab de `www-data` suffit :

```cron
* * * * * cd /var/www/monapp/current && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Sauvegarder

Une sauvegarde quotidienne de la base, gardée quatorze jours et copiée hors du serveur. Et un test de restauration chaque mois : une sauvegarde jamais restaurée n'en est pas une.

## Pour finir

Un VPS de 2 Go bien réglé encaisse sans difficulté plusieurs milliers de visiteurs par jour pour une application Laravel classique. Le secret n'est pas la puissance, ce sont les réglages : le swap, le nombre de processus PHP-FPM, OPcache, et des tâches de fond surveillées par systemd.
MD,
];
