<?php

/*
| Questions de démonstration (1/2). Voir QuestionSeeder.
| age : position dans la fenêtre de DemoClock (1 = début, 0 = maintenant) ; votes : votes +1 reçus ;
| accepted : réponse acceptée par l'auteur de la question ; down : votes −1 reçus.
*/

return [
    [
        'author' => 'Moussa Diop', 'age' => 0.97, 'votes' => 9,
        'title' => 'Webhook Wave : comment vérifier la signature dans Laravel ?',
        'tags' => ['Wave', 'Laravel', 'PHP'],
        'body' => <<<'MD'
Je reçois bien les webhooks de Wave Checkout sur mon endpoint, mais pour l'instant je ne vérifie rien : n'importe qui pourrait appeler l'URL et marquer une commande comme payée.

Wave envoie un en-tête `Wave-Signature` qui ressemble à ça :

```
t=1696945000,v1=3f1c8e0b6a...
```

Comment le vérifier proprement dans Laravel ? Et où placer ce contrôle : contrôleur ou middleware ?
MD,
        'answers' => [
            [
                'author' => 'Rodrigue Hounkpatin', 'votes' => 7, 'accepted' => true, 'after' => 3,
                'body' => <<<'MD'
Fais-le dans un middleware : le contrôleur ne voit ainsi que des requêtes authentiques. La signature est un HMAC-SHA256 du timestamp suivi du corps **brut** de la requête, calculé avec ton secret de webhook.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyWaveSignature
{
    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->header('Wave-Signature');
        parse_str(str_replace(',', '&', $header), $parts);

        $timestamp = $parts['t'] ?? '';
        $expected = hash_hmac('sha256', $timestamp.$request->getContent(), config('services.wave.webhook_secret'));

        // Refuse aussi les messages trop vieux (rejeu).
        abort_unless(
            hash_equals($expected, $parts['v1'] ?? '') && abs(time() - (int) $timestamp) < 300,
            401
        );

        return $next($request);
    }
}
```

Deux pièges :

1. Utilise `$request->getContent()`, jamais le JSON décodé puis réencodé : un seul espace de différence et la signature ne correspond plus.
2. Exclus la route de la protection CSRF (`$middleware->validateCsrfTokens(except: ['webhooks/wave'])` dans `bootstrap/app.php`).
MD,
            ],
            [
                'author' => 'Koffi Mensah', 'votes' => 4, 'after' => 9,
                'body' => <<<'MD'
En complément : traite le webhook de façon **idempotente**. Wave peut renvoyer le même événement plusieurs fois si ton serveur répond lentement.

Garde l'identifiant de l'événement en base avec une contrainte unique, et réponds `200` tout de suite si tu l'as déjà vu :

```php
if (WaveEvent::where('event_id', $payload['id'])->exists()) {
    return response()->noContent();
}
```

Et réponds vite : fais le travail lourd (e-mail, facture) dans un job en file d'attente.
MD,
            ],
        ],
    ],
    [
        'author' => 'Aïssatou Koné', 'age' => 0.93, 'votes' => 11,
        'title' => 'Flutter hors ligne : comment synchroniser les ventes quand le réseau revient ?',
        'tags' => ['Flutter', 'Hors ligne', 'Dart'],
        'body' => <<<'MD'
Mon appli sert à des commerçantes du marché d'Adjamé. Elles enregistrent des ventes toute la journée, souvent sans réseau. Aujourd'hui je stocke tout dans SQLite (sqflite) et j'envoie au serveur avec un bouton « Synchroniser ».

Je voudrais que la synchronisation se fasse toute seule dès que la connexion revient, sans perdre ni dupliquer de ventes. Quelle architecture vous utilisez ?
MD,
        'answers' => [
            [
                'author' => 'Rasmata Ouédraogo', 'votes' => 6, 'after' => 5,
                'body' => <<<'MD'
J'ai le même besoin pour une appli agricole. Ce qui marche chez moi : une **file d'opérations en attente** dans SQLite, vidée dès que le réseau revient.

1. Chaque vente reçoit un identifiant généré sur le téléphone (UUID). Le serveur l'utilise comme clé : renvoyer deux fois la même vente ne crée pas de doublon.
2. Une table `pending_ops` garde ce qui n'est pas encore parti.
3. On écoute la connectivité et on vide la file.

```dart
import 'package:connectivity_plus/connectivity_plus.dart';

class SyncService {
  SyncService(this.db, this.api);
  final AppDatabase db;
  final ApiClient api;
  bool _running = false;

  void start() {
    Connectivity().onConnectivityChanged.listen((results) {
      if (!results.contains(ConnectivityResult.none)) flush();
    });
  }

  Future<void> flush() async {
    if (_running) return;
    _running = true;
    try {
      for (final op in await db.pendingOps()) {
        await api.pushSale(op.payload); // le serveur ignore un UUID déjà reçu
        await db.deleteOp(op.id);
      }
    } finally {
      _running = false;
    }
  }
}
```

Attention : « connecté au Wi-Fi » ne veut pas dire « Internet marche ». Si l'appel échoue, garde l'opération et réessaie plus tard.
MD,
            ],
            [
                'author' => 'Koffi Mensah', 'votes' => 9, 'accepted' => true, 'after' => 14,
                'body' => <<<'MD'
La réponse de Rasmata est la bonne base. Ajoute deux choses pour que ça tienne en production :

**1. Une synchronisation en arrière-plan**, même si l'appli est fermée. Avec `workmanager`, Android relance la tâche quand le réseau est disponible :

```dart
Workmanager().registerPeriodicTask(
  'sync-ventes',
  'syncVentes',
  frequency: const Duration(minutes: 15),
  constraints: Constraints(networkType: NetworkType.connected),
);
```

**2. Des lots, pas des appels un par un.** En 3G, 200 requêtes de 1 Ko coûtent bien plus cher qu'une requête de 200 Ko. Envoie les ventes par paquets de 50, et fais répondre au serveur la liste des UUID acceptés : tu ne supprimes localement que ceux-là.

Côté serveur, un `upsert` sur l'UUID suffit :

```php
Sale::upsert($rows, uniqueBy: ['uuid'], update: ['amount', 'sold_at']);
```
MD,
            ],
            [
                'author' => 'Cheikh Fall', 'votes' => 1, 'after' => 40,
                'body' => <<<'MD'
Merci pour ce fil, je débute avec Flutter et je partais sur Firebase pour ça. Est-ce que Firestore avec la persistance hors ligne ne fait pas déjà tout ce travail ? Ça me semble plus simple pour un premier projet, même si on dépend de Google.
MD,
            ],
        ],
    ],
    [
        'author' => 'Ibrahima Sow', 'age' => 0.9, 'votes' => 6,
        'title' => 'Mon menu USSD se perd entre les étapes, comment garder l\'état ?',
        'tags' => ['USSD', 'PHP'],
        'body' => <<<'MD'
Je fais un petit service USSD avec Africa's Talking pour l'association de mon quartier (cotisations). Mon script PHP reçoit `sessionId`, `phoneNumber` et `text`, mais je ne sais pas comment savoir à quelle étape du menu se trouve l'utilisateur. J'ai essayé `$_SESSION`, ça ne marche pas.
MD,
        'answers' => [
            [
                'author' => 'Christian Mbarga', 'votes' => 8, 'accepted' => true, 'after' => 2,
                'body' => <<<'MD'
Pas besoin de session : avec Africa's Talking, le champ `text` contient **tout le parcours**, chaque saisie séparée par `*`. Au premier appel il est vide, puis `1`, puis `1*500`, etc. Tu reconstruis l'étape à partir de lui.

Ta réponse commence par `CON` si le menu continue, par `END` s'il se termine.

```php
<?php

$text = $_POST['text'] ?? '';
$steps = $text === '' ? [] : explode('*', $text);

header('Content-Type: text/plain');

echo match (true) {
    count($steps) === 0 => "CON Bienvenue\n1. Payer ma cotisation\n2. Voir mon solde",
    $steps === ['1'] => "CON Montant en FCFA ?",
    $steps[0] === '1' && count($steps) === 2 => "CON Confirmer " . (int) $steps[1] . " FCFA ?\n1. Oui\n2. Non",
    $steps[0] === '1' && ($steps[2] ?? null) === '1' => "END Merci, votre paiement est en cours.",
    $steps === ['2'] => "END Votre solde : " . solde($_POST['phoneNumber']) . " FCFA",
    default => "END Choix invalide.",
};
```

Garde chaque écran court : beaucoup de téléphones coupent au-delà de 160 caractères environ.
MD,
            ],
            [
                'author' => 'Ousmane Issoufou', 'votes' => 3, 'after' => 20,
                'body' => <<<'MD'
Et pense au **délai** : une session USSD expire vite (souvent autour de 20 secondes par écran selon l'opérateur). Si tu dois appeler une API lente (paiement), réponds `END` avec « Vous allez recevoir une confirmation par SMS » et fais le traitement après coup.
MD,
            ],
        ],
    ],
    [
        'author' => 'Nadège Dossou', 'age' => 0.88, 'votes' => 5,
        'title' => 'React : mon useEffect s\'exécute deux fois au chargement',
        'tags' => ['React', 'JavaScript'],
        'body' => <<<'MD'
Dans mon composant, j'appelle mon API dans un `useEffect` avec un tableau de dépendances vide. En développement, je vois deux requêtes identiques dans l'onglet Réseau. Est-ce que mon code est faux ?

```jsx
useEffect(() => {
  fetch('/api/produits').then(r => r.json()).then(setProduits);
}, []);
```
MD,
        'answers' => [
            [
                'author' => 'Fatou Ndiaye', 'votes' => 7, 'accepted' => true, 'after' => 1,
                'body' => <<<'MD'
Ton code n'est pas faux : c'est le **StrictMode** de React 18 qui monte, démonte puis remonte chaque composant en développement, pour t'aider à repérer les effets qui ne se nettoient pas. En production, il n'y a qu'une requête.

Profites-en pour ajouter le nettoyage, qui évite aussi d'écrire dans un composant démonté :

```jsx
useEffect(() => {
  const controller = new AbortController();

  fetch('/api/produits', { signal: controller.signal })
    .then((r) => r.json())
    .then(setProduits)
    .catch((e) => {
      if (e.name !== 'AbortError') throw e;
    });

  return () => controller.abort();
}, []);
```
MD,
            ],
            [
                'author' => 'Patrick Kabongo', 'votes' => 2, 'after' => 6,
                'body' => <<<'MD'
Pour les données serveur, regarde aussi TanStack Query : cache, déduplication et nouvelle tentative automatique quand la connexion revient. Très pratique quand le réseau est instable.
MD,
            ],
        ],
    ],
    [
        'author' => 'Mamadou Bah', 'age' => 0.86, 'votes' => 8,
        'title' => 'Orange Money Web Payment : quelle devise utiliser en sandbox ?',
        'tags' => ['Orange Money', 'PHP'],
        'body' => <<<'MD'
J'intègre l'API Orange Money Web Payment. En sandbox, je reçois une erreur quand j'envoie `"currency": "GNF"`. La documentation n'est pas claire sur ce point. Quelqu'un a déjà réussi un paiement de test ?
MD,
        'answers' => [
            [
                'author' => 'Moussa Diop', 'votes' => 9, 'accepted' => true, 'after' => 4,
                'body' => <<<'MD'
Oui : en environnement de développement, la devise est **`OUV`**, quel que soit le pays. Tu passeras à la vraie devise (XOF, GNF…) avec les identifiants de production.

Le parcours complet :

```php
// 1. Jeton OAuth (Basic = base64 de client_id:client_secret)
$token = Http::asForm()
    ->withHeaders(['Authorization' => 'Basic '.config('services.orange.basic')])
    ->post('https://api.orange.com/oauth/v3/token', ['grant_type' => 'client_credentials'])
    ->json('access_token');

// 2. Création du paiement
$payment = Http::withToken($token)
    ->post('https://api.orange.com/orange-money-webpay/dev/v1/webpayment', [
        'merchant_key' => config('services.orange.merchant_key'),
        'currency' => 'OUV',
        'order_id' => $order->reference,
        'amount' => $order->amount,
        'return_url' => route('paiement.retour'),
        'cancel_url' => route('paiement.annule'),
        'notif_url' => route('webhooks.orange'),
        'lang' => 'fr',
        'reference' => 'Boutique',
    ])->json();

// 3. Redirection vers $payment['payment_url'] ; garde $payment['notif_token']
```

Garde le `notif_token` : la notification envoyée sur `notif_url` le contient, c'est ce qui te permet de vérifier qu'elle vient bien d'Orange.
MD,
            ],
            [
                'author' => 'Awa Sarr', 'votes' => 3, 'after' => 18,
                'body' => <<<'MD'
Côté interface, un détail qui change tout : affiche clairement « Vous allez être redirigé vers Orange Money » avant le clic. Sans ça, beaucoup d'utilisateurs pensent à une arnaque et ferment la page.
MD,
            ],
        ],
    ],
    [
        'author' => 'Fatou Ndiaye', 'age' => 0.84, 'votes' => 7,
        'title' => 'Comment rendre une page Laravel vraiment légère pour la 3G ?',
        'tags' => ['Laravel', '3G', 'Performance'],
        'body' => <<<'MD'
Notre tableau de bord met plus de 8 secondes à s'afficher sur une connexion 3G à Thiès. Sur la fibre du bureau, c'est instantané. Par où commencer ?
MD,
        'answers' => [
            [
                'author' => 'Rodrigue Hounkpatin', 'votes' => 10, 'accepted' => true, 'after' => 3,
                'body' => <<<'MD'
Mesure d'abord : Chrome DevTools, onglet Réseau, profil « Slow 3G ». Ensuite, dans l'ordre de ce qui rapporte le plus chez nous :

1. **Le poids des images** : WebP, dimensions réelles, `loading="lazy"` sous la ligne de flottaison. C'est souvent 70 % du poids.
2. **Le JavaScript** : n'envoie pas un framework complet pour trois menus déroulants. Du HTML rendu par Laravel avec un peu d'Alpine.js suffit souvent.
3. **La compression** : vérifie que Nginx envoie bien du gzip (ou brotli).
4. **Le cache HTTP** des fichiers de build : ils sont versionnés par Vite, ils peuvent être gardés un an.

```nginx
gzip on;
gzip_types text/css application/javascript application/json image/svg+xml;

location /build/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

5. **Côté serveur** : pagination partout, et pas de requête N+1. Active `Model::preventLazyLoading()` en local, il te montrera chaque oubli de `with()`.
MD,
            ],
            [
                'author' => 'Kadiatou Diallo', 'votes' => 4, 'after' => 10,
                'body' => <<<'MD'
J'ajoute les **polices** : une police Google en 6 graisses, c'est facilement 300 Ko. Héberge-les toi-même, en WOFF2, avec seulement les graisses utilisées, et `font-display: swap` pour que le texte s'affiche tout de suite.
MD,
            ],
            [
                'author' => 'Junior Tchoupo', 'votes' => 0, 'after' => 30,
                'body' => <<<'MD'
Question bête : est-ce qu'un CDN comme Cloudflare aide vraiment si le serveur est à Dakar et les utilisateurs aussi ?
MD,
            ],
        ],
    ],
    [
        'author' => 'Cheikh Fall', 'age' => 0.08, 'votes' => 2,
        'title' => 'Flutter : faut-il apprendre Provider, Riverpod ou Bloc en premier ?',
        'tags' => ['Flutter', 'Dart'],
        'body' => <<<'MD'
Je débute avec Flutter et chaque tutoriel utilise une gestion d'état différente. Pour une première appli (une liste de prises de poisson avec quelques formulaires), lequel choisir sans me perdre ?
MD,
        'answers' => [
            [
                'author' => 'Aïssatou Koné', 'votes' => 3, 'after' => 2,
                'body' => <<<'MD'
Pour une première appli : commence par `setState` et un `StatefulWidget`, vraiment. Tu comprendras le problème que les autres solutions résolvent.

Quand tu dois partager un état entre plusieurs écrans, passe à **Riverpod** : peu de code, pas besoin de `BuildContext` pour lire l'état, et il se teste facilement.

```dart
final prisesProvider = StateNotifierProvider<PrisesNotifier, List<Prise>>(
  (ref) => PrisesNotifier(),
);
```

Bloc est très bien dans une grosse équipe, mais c'est beaucoup de cérémonie pour débuter.
MD,
            ],
        ],
    ],
    [
        'author' => 'Yao Kouassi', 'age' => 0.82, 'votes' => 5,
        'title' => 'MTN MoMo sandbox : erreur 500 sur requesttopay',
        'tags' => ['MTN MoMo', 'API'],
        'body' => <<<'MD'
J'ai créé mon API user et ma clé dans la sandbox MTN MoMo, j'obtiens bien un jeton. Mais `POST /collection/v1_0/requesttopay` me renvoie une 500 sans explication. Mon corps de requête :

```json
{
  "amount": "5000",
  "currency": "XOF",
  "externalId": "CMD-1042",
  "payer": { "partyIdType": "MSISDN", "partyId": "2250707070707" },
  "payerMessage": "Commande 1042",
  "payeeNote": "Merci"
}
```
MD,
        'answers' => [
            [
                'author' => 'Kwame Asante', 'votes' => 8, 'accepted' => true, 'after' => 5,
                'body' => <<<'MD'
Le coupable est la devise : la **sandbox n'accepte que `EUR`**. Remplace `XOF` par `EUR` pour tes tests, tu passeras à la devise locale en production.

Vérifie aussi les en-têtes, il en manque souvent un :

```bash
curl -X POST https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Reference-Id: $(uuidgen)" \
  -H "X-Target-Environment: sandbox" \
  -H "Ocp-Apim-Subscription-Key: $SUBSCRIPTION_KEY" \
  -H "Content-Type: application/json" \
  -d '{"amount":"5000","currency":"EUR","externalId":"CMD-1042","payer":{"partyIdType":"MSISDN","partyId":"46733123450"},"payerMessage":"Commande 1042","payeeNote":"Merci"}'
```

La réponse est un `202` sans corps. Tu lis ensuite le statut avec `GET /collection/v1_0/requesttopay/{X-Reference-Id}`. Garde donc bien l'UUID que tu as envoyé.
MD,
            ],
            [
                'author' => 'Wanjiru Kamau', 'votes' => 4, 'after' => 16,
                'body' => <<<'MD'
Même logique qu'avec M-Pesa chez nous : le paiement est **asynchrone**. Ne bloque pas la page en attendant : affiche « Valide le paiement sur ton téléphone », puis interroge le statut toutes les 5 secondes pendant 2 minutes, ou mieux, utilise le callback.
MD,
            ],
        ],
    ],
    [
        'author' => 'Edem Agbeko', 'age' => 0.8, 'votes' => 4,
        'title' => 'pandas : mes montants en FCFA sont lus comme du texte',
        'tags' => ['Python', 'Data science'],
        'body' => <<<'MD'
L'export Excel de l'ONG donne un CSV avec des montants comme `1 250 000,50` et un point-virgule comme séparateur. `pd.read_csv` me donne une colonne de type `object` et je ne peux rien additionner.
MD,
        'answers' => [
            [
                'author' => 'Amine Trabelsi', 'votes' => 6, 'accepted' => true, 'after' => 3,
                'body' => <<<'MD'
Dis à pandas que le séparateur est `;`, la virgule décimale `,` et le séparateur de milliers une espace. Attention : Excel met souvent une **espace insécable** (`\xa0`) ou une espace fine, pas une espace normale.

```python
import pandas as pd

df = pd.read_csv("dons.csv", sep=";", decimal=",", encoding="utf-8")

df["montant"] = (
    df["montant"]
    .astype(str)
    .str.replace(r"[\s  ]", "", regex=True)
    .str.replace(",", ".", regex=False)
    .astype(float)
)

print(df["montant"].sum())
```

Le FCFA n'a pas de centimes en pratique : tu peux convertir en `int` une fois la colonne propre.
MD,
            ],
        ],
    ],
    [
        'author' => 'Abdoul Kaboré', 'age' => 0.78, 'votes' => 10,
        'title' => 'Déployer Laravel sur un petit VPS de 1 Go : la configuration qui tient',
        'tags' => ['Laravel', 'Linux', 'DevOps'],
        'body' => <<<'MD'
On veut héberger nos applis sur un VPS local (1 vCPU, 1 Go de RAM) plutôt qu'à l'étranger, pour la latence et le prix. Quelle configuration Nginx + PHP-FPM + PostgreSQL vous conseillez pour que ça ne tombe pas au premier pic ?
MD,
        'answers' => [
            [
                'author' => 'Abdoul Kaboré', 'votes' => 5, 'after' => 30,
                'body' => <<<'MD'
Je réponds à ma propre question après deux semaines de tests, ça servira à d'autres. Le plus important a été le **swap** : sans lui, PostgreSQL se faisait tuer par le noyau pendant les `composer install`.

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```
MD,
            ],
            [
                'author' => 'Koffi Mensah', 'votes' => 9, 'accepted' => true, 'after' => 8,
                'body' => <<<'MD'
Sur 1 Go, le réglage qui compte est le nombre de processus PHP-FPM. Chaque processus Laravel prend 40 à 60 Mo : au-delà de 8 à 10, tu manques de mémoire.

`/etc/php/8.2/fpm/pool.d/www.conf` :

```ini
pm = dynamic
pm.max_children = 8
pm.start_servers = 2
pm.min_spare_servers = 2
pm.max_spare_servers = 4
pm.max_requests = 500
```

Pour PostgreSQL, baisse `shared_buffers` à 128 Mo et `max_connections` à 30. Active OPcache et mets en cache la configuration :

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Enfin, lance le worker de file d'attente avec systemd, pas dans un `screen` :

```ini
[Service]
User=www-data
ExecStart=/usr/bin/php /var/www/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
```
MD,
            ],
            [
                'author' => 'Youssef El Amrani', 'votes' => 3, 'after' => 22,
                'body' => <<<'MD'
Ajoute un `fail2ban` sur SSH et désactive la connexion par mot de passe dès le premier jour : un VPS neuf reçoit des tentatives de connexion dans l'heure qui suit sa création.
MD,
            ],
        ],
    ],
    [
        'author' => 'Seydou Coulibaly', 'age' => 0.76, 'votes' => 3,
        'title' => 'Passer de WordPress à Laravel : par quoi commencer ?',
        'tags' => ['Laravel', 'WordPress', 'PHP'],
        'body' => <<<'MD'
Je fais des sites WordPress depuis trois ans. Je veux passer à Laravel pour faire de vraies applications (gestion de stock pour les commerçants). Quel parcours vous conseillez quand on connaît déjà PHP ?
MD,
        'answers' => [
            [
                'author' => 'Sitsofe Amouzou', 'votes' => 5, 'accepted' => true, 'after' => 6,
                'body' => <<<'MD'
J'ai fait exactement ce chemin. Ce qui m'a aidée :

1. **Le PHP moderne d'abord** : classes, espaces de noms, Composer. Dans WordPress on écrit beaucoup de fonctions globales, Laravel est orienté objet.
2. **Le Bootcamp officiel de Laravel**, puis une vraie petite appli à toi : un CRUD de produits avec authentification.
3. **Eloquent et les migrations** : c'est le plus grand changement par rapport à `$wpdb`.
4. Ensuite seulement, **Livewire** : tu fais des interfaces dynamiques sans quitter PHP, très confortable quand on vient de WordPress.

Et garde tes clients WordPress pendant la transition : ce sont eux qui paient ton apprentissage.
MD,
            ],
            [
                'author' => 'Rodrigue Hounkpatin', 'votes' => 3, 'after' => 15,
                'body' => <<<'MD'
Je confirme pour Livewire. Et lis le code des autres : prends un petit projet Laravel open source et suis le chemin d'une requête, de la route jusqu'à la vue. On apprend énormément comme ça.
MD,
            ],
        ],
    ],
    [
        'author' => 'Linda Ngo Bassa', 'age' => 0.74, 'votes' => 6,
        'title' => 'Valider un numéro de téléphone camerounais et ivoirien côté formulaire',
        'tags' => ['JavaScript', 'React Native'],
        'body' => <<<'MD'
Nos utilisateurs saisissent leur numéro de toutes les façons : `6 99 12 34 56`, `+237699123456`, `00237…`. Je veux les normaliser au format international avant d'appeler l'API de paiement. Il existe une bonne pratique ?
MD,
        'answers' => [
            [
                'author' => 'Jean-Paul Habimana', 'votes' => 7, 'accepted' => true, 'after' => 4,
                'body' => <<<'MD'
N'écris pas tes propres expressions régulières pour chaque pays : les plans de numérotation changent (la Côte d'Ivoire est passée à 10 chiffres en 2021). Utilise `libphonenumber-js`, maintenu à jour :

```js
import { parsePhoneNumberFromString } from 'libphonenumber-js';

export function normaliserNumero(saisie, pays = 'CM') {
  const numero = parsePhoneNumberFromString(saisie, pays);
  if (!numero || !numero.isValid()) return null;
  return numero.number; // ex. +237699123456 (format E.164)
}

normaliserNumero('6 99 12 34 56');              // '+237699123456'
normaliserNumero('07 07 07 07 07', 'CI');       // '+2250707070707'
```

Stocke toujours le format E.164 en base, et affiche le format national à l'utilisateur.
MD,
            ],
        ],
    ],
    [
        'author' => 'Junior Tchoupo', 'age' => 0.07, 'votes' => 3,
        'title' => 'Où stocker un jeton JWT dans une appli web : localStorage ou cookie ?',
        'tags' => ['Cybersécurité', 'JavaScript'],
        'body' => <<<'MD'
Je fais une API Node.js avec une interface React. Tous les tutoriels mettent le JWT dans `localStorage`, mais j'ai lu que c'était dangereux. Quelle est la bonne pratique ?
MD,
        'answers' => [
            [
                'author' => 'Patrick Kabongo', 'votes' => 4, 'after' => 3,
                'body' => <<<'MD'
`localStorage` est lisible par n'importe quel script de la page : une seule faille XSS (une dépendance compromise, un champ mal échappé) et le jeton est volé.

Si ton interface et ton API sont sur le même domaine, préfère un **cookie `HttpOnly`, `Secure`, `SameSite=Lax`** : le JavaScript ne peut pas le lire.

```js
res.cookie('session', token, {
  httpOnly: true,
  secure: true,
  sameSite: 'lax',
  maxAge: 1000 * 60 * 60 * 8,
});
```

Avec un cookie, protège-toi du CSRF : `SameSite=Lax` couvre l'essentiel, ajoute un jeton CSRF pour les actions sensibles.
MD,
            ],
        ],
    ],
    [
        'author' => 'Hery Rakotomalala', 'age' => 0.72, 'votes' => 4,
        'title' => 'PostgreSQL : ma recherche sur les noms de produits est très lente',
        'tags' => ['PostgreSQL', 'Python'],
        'body' => <<<'MD'
J'ai 300 000 produits et une recherche `WHERE nom ILIKE '%riz%'`. Ça prend plus de 2 secondes. J'ai mis un index sur `nom`, rien ne change.
MD,
        'answers' => [
            [
                'author' => 'Christian Mbarga', 'votes' => 6, 'accepted' => true, 'after' => 5,
                'body' => <<<'MD'
Un index B-tree classique ne sert à rien pour un `LIKE` qui commence par `%`. Il te faut un **index trigramme** :

```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX produits_nom_trgm ON produits USING gin (nom gin_trgm_ops);
```

Ta requête `ILIKE '%riz%'` l'utilise alors directement. Vérifie avec `EXPLAIN ANALYZE` : tu dois voir `Bitmap Index Scan on produits_nom_trgm`.

Si tu cherches plutôt des mots dans de longs textes (descriptions), passe à la recherche plein texte avec `to_tsvector('french', …)`, qui gère aussi les pluriels.
MD,
            ],
            [
                'author' => 'Youssef El Amrani', 'votes' => 2, 'after' => 12,
                'body' => <<<'MD'
Et pour les accents (« café » trouvé avec « cafe »), ajoute l'extension `unaccent` et indexe `unaccent(nom)` via une fonction immuable. Sinon tes utilisateurs sur clavier de téléphone ne trouveront rien.
MD,
            ],
        ],
    ],
    [
        'author' => 'Awa Sarr', 'age' => 0.7, 'votes' => 8,
        'title' => 'Comment présenter un paiement Wave pour que les gens aient confiance ?',
        'tags' => ['UI/UX', 'Wave'],
        'body' => <<<'MD'
Sur notre parcours d'achat, beaucoup d'utilisateurs abandonnent au moment du paiement Wave. Nos tests montrent qu'ils ne comprennent pas ce qui va se passer. Vous avez des bonnes pratiques d'interface ?
MD,
        'answers' => [
            [
                'author' => 'Adjoua N\'Guessan', 'votes' => 6, 'accepted' => true, 'after' => 6,
                'body' => <<<'MD'
Ce qui a fait baisser nos abandons :

- **Annoncer l'étape suivante** juste au-dessus du bouton : « Tu vas être redirigé vers Wave pour valider 15 000 FCFA. »
- **Le montant en gros**, en FCFA, avec les frais s'il y en a. Jamais de surprise au moment de valider.
- **Le logo et le nom exacts** du moyen de paiement, que les gens reconnaissent.
- **Un retour clair** : une page « Paiement reçu » avec le numéro de commande, et un SMS de confirmation.
- **Une porte de sortie** : « Le paiement a échoué ? Réessaie ou choisis Orange Money. »

Et teste avec de vrais utilisateurs, pas seulement l'équipe : 5 personnes suffisent pour trouver les plus gros blocages.
MD,
            ],
        ],
    ],
    [
        'author' => 'Kadiatou Diallo', 'age' => 0.68, 'votes' => 5,
        'title' => 'Django : 300 requêtes SQL pour afficher une liste de cours',
        'tags' => ['Django', 'Python', 'PostgreSQL'],
        'body' => <<<'MD'
La Django Debug Toolbar m'indique plus de 300 requêtes pour une page qui liste 100 cours avec leur formateur et leurs catégories. Comment réduire ça ?
MD,
        'answers' => [
            [
                'author' => 'Koffi Mensah', 'votes' => 8, 'accepted' => true, 'after' => 2,
                'body' => <<<'MD'
Classique N+1 : chaque `cours.formateur` et chaque `cours.categories.all()` déclenche une requête. Utilise `select_related` pour les clés étrangères (une jointure) et `prefetch_related` pour les relations multiples (une requête de plus) :

```python
cours = (
    Cours.objects
    .select_related("formateur")
    .prefetch_related("categories")
    .order_by("-cree_le")[:100]
)
```

Tu passes de 300 à 2 requêtes. Si tu n'as besoin que de quelques champs, ajoute `.only("titre", "formateur__nom")` : en 3G, moins de données, c'est aussi une page plus rapide.
MD,
            ],
            [
                'author' => 'Aisha Bello', 'votes' => 3, 'after' => 9,
                'body' => <<<'MD'
Pour ne plus jamais laisser passer un N+1, ajoute un test qui compte les requêtes :

```python
def test_liste_cours(self):
    with self.assertNumQueries(2):
        self.client.get("/cours/")
```
MD,
            ],
        ],
    ],
    [
        'author' => 'Chinedu Okafor', 'age' => 0.66, 'votes' => 6,
        'title' => 'Envoyer des SMS de confirmation avec Africa\'s Talking depuis Node.js',
        'tags' => ['SMS', 'Node.js'],
        'body' => <<<'MD'
Après un paiement, je veux envoyer un SMS de confirmation à mes clients au Sénégal et en Côte d'Ivoire. Je travaille d'habitude en anglais, désolé si mon français est approximatif. Quelqu'un a un exemple simple avec Africa's Talking ?
MD,
        'answers' => [
            [
                'author' => 'Mariam Traoré', 'votes' => 6, 'accepted' => true, 'after' => 3,
                'body' => <<<'MD'
Avec le SDK officiel, c'est court. Commence avec le nom d'utilisateur `sandbox` pour tester sans frais, sur leur simulateur :

```js
import AfricasTalking from 'africastalking';

const at = AfricasTalking({
  apiKey: process.env.AT_API_KEY,
  username: process.env.AT_USERNAME, // 'sandbox' pour les tests
});

export async function confirmerPaiement(numero, montant, reference) {
  return at.SMS.send({
    to: [numero], // format international : +221771234567
    message: `Paiement de ${montant} FCFA reçu (réf. ${reference}). Merci !`,
  });
}
```

Deux conseils :
- Reste sous **160 caractères sans accents** si tu peux : les caractères accentués font passer le message en Unicode, limité à 70 caractères par SMS, donc facturé double ou triple.
- Envoie-le depuis une file d'attente : si l'opérateur est lent, ton webhook de paiement ne doit pas attendre.
MD,
            ],
        ],
    ],
    [
        'author' => 'Grâce Mukendi', 'age' => 0.64, 'votes' => 2,
        'title' => 'Après .NET, Flutter ou React Native pour une première appli mobile ?',
        'tags' => ['Flutter', 'React Native'],
        'body' => <<<'MD'
Je viens du C# et de .NET. Je veux faire une appli mobile pour suivre les interventions des techniciens sur les sites miniers. Flutter ou React Native : lequel sera le plus naturel pour moi ?
MD,
        'answers' => [
            [
                'author' => 'Ama Owusu', 'votes' => 3, 'accepted' => true, 'after' => 7,
                'body' => <<<'MD'
Venant du C#, Dart va te sembler très familier : typage fort, classes, `async`/`await`, null safety. Flutter est un bon choix pour toi, et ses performances sur les téléphones modestes sont bonnes.

React Native a du sens si ton équipe fait déjà du React pour le web.

Pour ton cas (sites miniers, réseau incertain), le vrai critère est le **mode hors ligne** : regarde la discussion sur la synchronisation Flutter dans les questions, elle s'applique directement.
MD,
            ],
            [
                'author' => 'Linda Ngo Bassa', 'votes' => 1, 'after' => 20,
                'body' => <<<'MD'
Et si tu restes fidèle à .NET : .NET MAUI existe, mais la communauté est plus petite et on trouve moins d'aide en français. Pour une première appli, je partirais sur Flutter.
MD,
            ],
        ],
    ],
];
