<?php

return [
    'author' => 'Rodrigue Hounkpatin', 'category' => 'best_practice', 'age' => 0.85, 'useful' => 12,
    'title' => 'Laravel rapide en 3G : cache, images et pagination',
    'tags' => ['Laravel', '3G', 'Performance'],
    'body' => <<<'MD'
Dans notre agence à Cotonou, nous avons une règle : une page doit être utilisable en moins de trois secondes sur une connexion 3G moyenne. Pas sur la fibre du bureau, sur le téléphone d'un client à Parakou. Voici les pratiques qui nous ont fait passer de pages de 3 Mo à des pages de moins de 300 Ko, sans changer de framework.

## Mesurer avant d'optimiser

Ouvre Chrome DevTools, onglet **Réseau**, choisis le profil « Slow 4G » ou « 3G », coche « Désactiver le cache » et recharge. Note trois chiffres : le poids total transféré, le nombre de requêtes et le moment où le contenu principal apparaît. Lighthouse en mode mobile complète bien ce premier regard.

Refais la mesure après chaque changement. Sans chiffres, on optimise au hasard.

## 1. Les images : la moitié du problème

Sur presque tous les projets que nous auditons, les images pèsent plus que tout le reste réuni.

- **Le bon format** : WebP est lu par tous les navigateurs récents et pèse 25 à 35 % de moins qu'un JPEG équivalent.
- **La bonne taille** : une photo de 4 000 pixels affichée en 400 pixels, c'est 90 % de données jetées.
- **Le bon moment** : `loading="lazy"` pour tout ce qui est sous la ligne de flottaison.

Nous redimensionnons à l'envoi, avec Intervention Image :

```php
use Intervention\Image\ImageManager;

$path = 'produits/'.Str::uuid().'.webp';

Storage::disk('public')->put(
    $path,
    (string) ImageManager::gd()->read($request->file('photo'))->scaleDown(width: 800)->toWebp(quality: 75)
);
```

Et dans la vue, on donne toujours les dimensions pour éviter que la page saute pendant le chargement :

```blade
<img src="{{ Storage::url($produit->photo) }}" width="800" height="600" loading="lazy" alt="{{ $produit->nom }}">
```

## 2. Moins de JavaScript

Une application React complète pour afficher une liste de produits et deux menus déroulants, c'est souvent 300 Ko de JavaScript à télécharger, puis à exécuter sur un téléphone modeste. Laravel rend du HTML côté serveur : c'est un avantage, garde-le.

Pour l'interactivité, Livewire et Alpine.js couvrent la grande majorité des besoins. Et si une page n'a pas de composant Livewire, n'y charge pas Livewire : Alpine seul pèse une quinzaine de kilo-octets compressé.

## 3. Pas de requête N+1

Une liste de 50 commandes qui affiche le nom du client déclenche 51 requêtes si on oublie le chargement anticipé. En 3G, le serveur répond plus lentement, et tout le reste attend.

```php
$commandes = Commande::with(['client', 'produits'])
    ->latest()
    ->paginate(20);
```

Pour ne plus jamais en laisser passer, active la protection en développement, dans `AppServiceProvider` :

```php
Model::preventLazyLoading(! app()->isProduction());
```

Chaque oubli lève alors une exception en local, au lieu de ralentir la production.

## 4. Paginer partout

Aucune liste ne doit afficher « tout ». Vingt éléments par page, c'est le bon réglage pour la plupart des écrans mobiles. Pour les très longues listes, `simplePaginate()` évite même la requête de comptage :

```php
$messages = Message::latest()->simplePaginate(20);
```

## 5. Le cache, à trois niveaux

**Le cache de Laravel** pour les calculs coûteux qui changent peu, comme les statistiques d'un tableau de bord :

```php
$stats = Cache::remember('dashboard.stats', now()->addMinutes(10), fn () => [
    'ventes' => Vente::whereDate('created_at', today())->sum('montant'),
    'clients' => Client::count(),
]);
```

**Le cache de l'application** en production, en une commande dans ton script de déploiement :

```bash
php artisan optimize
```

**Le cache du navigateur** pour les fichiers de build. Vite ajoute une empreinte à chaque nom de fichier : ils peuvent être gardés un an sans risque.

```nginx
location /build/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

Un visiteur qui revient ne retélécharge alors ni CSS ni JavaScript.

## 6. Compresser ce qui part

Vérifie que Nginx compresse le HTML, le CSS, le JavaScript et le JSON. Le gain est souvent de 70 % sur le texte.

```nginx
gzip on;
gzip_comp_level 5;
gzip_types text/css application/javascript application/json image/svg+xml;
```

## 7. Les polices, ce détail qui pèse lourd

Une famille Google Fonts en six graisses, c'est facilement 300 Ko et une requête vers un serveur étranger. Héberge tes polices toi-même, en WOFF2, avec seulement les graisses utilisées (une police variable suffit souvent), et `font-display: swap` pour que le texte s'affiche tout de suite.

## Notre liste de contrôle avant chaque mise en ligne

- Page d'accueil sous 300 Ko hors polices.
- Images en WebP, redimensionnées, avec `width` et `height`.
- Aucune requête N+1 (protection active en local).
- Pagination sur chaque liste.
- `php artisan optimize` dans le déploiement.
- Gzip actif, cache long sur `/build/`.
- Mesure en profil 3G avant et après.

Rien de tout cela n'est spectaculaire. Mais appliqué ensemble, c'est ce qui fait qu'un commerçant de Parakou garde ton application au lieu de la désinstaller.
MD,
];
