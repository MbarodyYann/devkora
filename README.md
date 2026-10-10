# DevKora

**Le point de rencontre des devs africains.** Trouve des missions. Partage tes connaissances. Construis avec les tiens.

Projet de l'équipe **Baobab Stack** (Sénégal) pour le concours CADEV 2026 organisé par Systalink.

## Stack

| Couche | Choix |
|---|---|
| Langage | PHP 8.2 |
| Framework | Laravel 12, Fortify (authentification) |
| Interactivité | Livewire 3 sur les pages qui en ont besoin, Alpine.js seul ailleurs |
| Base de données | PostgreSQL 16 |
| Styles | Tailwind CSS 4, thème clair et sombre, polices auto-hébergées |
| Images | Intervention Image (photos de profil en WebP) |
| Tests | Pest 3, Playwright (mesure du débordement à 360 px) |

## Installation locale

Prérequis : PHP 8.2 (extensions pgsql, mbstring, intl, gd), Composer, Node.js 20 ou plus, PostgreSQL 16.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
createdb devkora
php artisan migrate --seed
php artisan storage:link
npm run build
```

Pour la connexion en un clic avec les comptes de démonstration (Moussa, Aïssatou, Koffi), mettre `APP_DEMO=true` dans `.env`. Mot de passe commun des comptes de démonstration : `password`.

## Tests

```bash
createdb devkora_test
npx playwright install chromium   # une seule fois, pour le test de débordement
php artisan test
```

## Usage de l'intelligence artificielle

- Claude Code (Anthropic) : code, tests et revue de code.
- ChatGPT (OpenAI) : image d'illustration de l'accueil.

## Équipe

**Baobab Stack** (Sénégal) : Yaya Niang (représentant), Aminata Sall.
