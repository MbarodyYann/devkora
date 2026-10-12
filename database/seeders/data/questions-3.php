<?php

/*
| Questions du jour (3/3) : posées dans les dernières 24 h, pour que l'accueil (« Ce que les devs se demandent
| en ce moment ») montre un site actif. hours : il y a combien d'heures, par rapport à DemoClock (au lieu de age).
| Même format que questions-1.php pour le reste.
*/

return [
    [
        'author' => 'Adjoua N\'Guessan', 'hours' => 40 / 60, 'votes' => 0,
        'title' => 'Orange Money : le paiement passe, mais le client ne revient jamais sur mon site',
        'tags' => ['Orange Money', 'Laravel', 'API'],
        'body' => <<<'MD'
J'intègre le Web Payment d'Orange Money pour une boutique à Abidjan. Le client paie sur la page d'Orange, reçoit bien le SMS de confirmation, mais il reste bloqué sur la page « Paiement effectué » au lieu de revenir sur ma `return_url`.

```php
$response = Http::withToken($token)->post($baseUrl.'/webpayment', [
    'merchant_key' => config('services.orange.merchant_key'),
    'currency' => 'OUV',
    'order_id' => $commande->reference,
    'amount' => $commande->montant,
    'return_url' => route('paiement.retour'),
    'cancel_url' => route('paiement.annule'),
    'notif_url' => route('paiement.notification'),
    'lang' => 'fr',
]);
```

En sandbox tout marche. En production, la notification arrive, mais pas la redirection. Quelqu'un a déjà eu ce cas ?
MD,
        'answers' => [],
    ],
    [
        'author' => 'Jean-Paul Habimana', 'hours' => 3, 'votes' => 0,
        'title' => 'Django : envoyer des SMS de rappel sans faire attendre l\'utilisateur',
        'tags' => ['Django', 'Python', 'SMS'],
        'body' => <<<'MD'
Notre appli de prise de rendez-vous pour des centres de santé à Kigali envoie un SMS de confirmation à chaque réservation. L'API SMS met parfois 5 à 8 secondes à répondre, et pendant ce temps la page tourne. Comment sortir l'envoi de la requête sans monter une grosse infrastructure ?
MD,
        'answers' => [
            [
                'author' => 'Wanjiru Kamau', 'votes' => 1, 'after' => 1,
                'body' => <<<'MD'
Pour un petit volume, pas besoin de Celery et de Redis tout de suite. Enregistre le SMS à envoyer en base, puis envoie-le avec une commande lancée chaque minute par cron :

```python
# rendezvous/management/commands/envoyer_sms.py
class Command(BaseCommand):
    def handle(self, *args, **options):
        for sms in SmsEnAttente.objects.filter(envoye_le__isnull=True)[:50]:
            try:
                fournisseur.envoyer(sms.numero, sms.texte)
                sms.envoye_le = timezone.now()
            except FournisseurIndisponible:
                sms.tentatives += 1
            sms.save()
```

```
* * * * * cd /srv/rdv && .venv/bin/python manage.py envoyer_sms
```

La réservation répond tout de suite, et un SMS raté est retenté à la minute suivante. Quand le volume grimpera, tu passeras à Celery sans toucher au modèle.
MD,
            ],
        ],
    ],
    [
        'author' => 'Hery Rakotomalala', 'hours' => 9, 'votes' => 1,
        'title' => 'PostgreSQL : ma recherche avec LIKE est lente sur 200 000 produits',
        'tags' => ['PostgreSQL', 'Performance', 'SQL Server'],
        'body' => <<<'MD'
Je migre le catalogue d'un grossiste d'Antananarivo de SQL Server vers PostgreSQL. La recherche utilise `WHERE nom ILIKE '%riz%'` et prend plus de 2 secondes sur 200 000 lignes. Un index B-tree sur `nom` ne change rien. Quelle est la bonne approche ?
MD,
        'answers' => [
            [
                'author' => 'Chinedu Okafor', 'votes' => 2, 'after' => 1,
                'body' => <<<'MD'
Un B-tree ne sert pas pour un `%` en début de motif. Il te faut un index trigramme :

```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX produits_nom_trgm ON produits USING gin (nom gin_trgm_ops);
```

Ta requête `ILIKE '%riz%'` reste identique et utilise l'index. Vérifie avec `EXPLAIN ANALYZE` : tu dois voir un `Bitmap Index Scan` sur `produits_nom_trgm`.
MD,
            ],
            [
                'author' => 'Fatou Ndiaye', 'votes' => 1, 'after' => 4,
                'body' => <<<'MD'
Si tes utilisateurs tapent plusieurs mots, parfois sans accents (« cafe moulu »), regarde aussi la recherche plein texte avec `unaccent` :

```sql
CREATE EXTENSION IF NOT EXISTS unaccent;
SELECT * FROM produits
WHERE to_tsvector('french', unaccent(nom)) @@ plainto_tsquery('french', unaccent('cafe moulu'));
```

Le trigramme reste meilleur pour les fautes de frappe et les bouts de mots ; le plein texte, pour les phrases.
MD,
            ],
        ],
    ],
    [
        'author' => 'Junior Tchoupo', 'hours' => 20, 'votes' => 3,
        'title' => 'Flutter : mon APK fait 60 Mo, comment l\'alléger pour mes utilisateurs en 3G ?',
        'tags' => ['Flutter', 'Android', '3G'],
        'body' => <<<'MD'
Mon appli de suivi de tontines pour Douala pèse 60 Mo. Beaucoup d'utilisateurs abandonnent le téléchargement en 3G ou manquent de place sur leur téléphone. Par où commencer pour réduire la taille ?
MD,
        'answers' => [
            [
                'author' => 'Aïssatou Koné', 'votes' => 4, 'accepted' => true, 'after' => 2,
                'body' => <<<'MD'
Ton APK universel embarque le code natif de trois architectures. Commence par là :

```bash
flutter build apk --release --split-per-abi --obfuscate --split-debug-info=build/symboles
```

Tu obtiens un APK par architecture ; la plupart des téléphones récents prennent `app-arm64-v8a-release.apk`, souvent deux à trois fois plus léger. Sur le Play Store, publie plutôt un App Bundle (`flutter build appbundle`) : Google sert à chacun la bonne version.

Ensuite, passe tes images en WebP et retire les polices et les paquets inutilisés (`flutter build apk --analyze-size` montre ce qui pèse).
MD,
            ],
        ],
    ],
];
