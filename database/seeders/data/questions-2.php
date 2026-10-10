<?php

/*
| Questions de démonstration (2/2). Voir questions-1.php pour le format.
*/

return [
    [
        'author' => 'Rodrigue Hounkpatin', 'age' => 0.62, 'votes' => 7,
        'title' => 'Laravel : quand utiliser une file d\'attente pour les paiements mobiles ?',
        'tags' => ['Laravel', 'MTN MoMo', 'Wave'],
        'body' => <<<'MD'
Dans notre agence, certains développeurs appellent l'API de paiement directement dans le contrôleur, d'autres passent par un job. Avec des API parfois lentes (plus de 10 secondes), quelle règle vous appliquez ?
MD,
        'answers' => [
            [
                'author' => 'Moussa Diop', 'votes' => 6, 'accepted' => true, 'after' => 2,
                'body' => <<<'MD'
Ma règle : **l'appel qui démarre le paiement reste synchrone** (l'utilisateur attend l'URL de redirection ou la demande sur son téléphone), **tout ce qui suit passe par une file** : vérification du statut, confirmation, SMS, facture.

```php
// Le webhook répond tout de suite, le traitement se fait en arrière-plan.
public function __invoke(Request $request)
{
    ConfirmerPaiement::dispatch($request->input('data.id'));

    return response()->noContent();
}
```

```php
class ConfirmerPaiement implements ShouldQueue
{
    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300];

    public function __construct(public string $paiementId) {}

    public function handle(WaveClient $wave): void
    {
        $statut = $wave->statutSession($this->paiementId); // relit la source de vérité
        // … met à jour la commande si le paiement est confirmé
    }
}
```

Le `backoff` progressif est précieux : quand l'API d'un opérateur tombe une heure, les paiements finissent par passer sans intervention.
MD,
            ],
            [
                'author' => 'Kwame Asante', 'votes' => 3, 'after' => 8,
                'body' => <<<'MD'
Ajoute aussi une tâche planifiée qui relance la vérification des paiements restés « en attente » depuis plus de 10 minutes. Les webhooks se perdent parfois, surtout pendant les coupures de l'opérateur.

```php
Schedule::job(new RelancerPaiementsEnAttente)->everyFiveMinutes();
```
MD,
            ],
            [
                'author' => 'Yao Kouassi', 'votes' => 2, 'after' => 20,
                'body' => <<<'MD'
Petit retour de terrain : avec `QUEUE_CONNECTION=database` sur un petit serveur, ça marche très bien. Pas besoin de Redis pour quelques centaines de paiements par jour.
MD,
            ],
        ],
    ],
    [
        'author' => 'Salma Benali', 'age' => 0.6, 'votes' => 4,
        'title' => 'Spring Boot : les dates s\'affichent avec une heure de décalage',
        'tags' => ['Spring Boot', 'Java', 'PostgreSQL'],
        'body' => <<<'MD'
Mes rendez-vous enregistrés à 10 h s'affichent à 11 h côté client. Le serveur est en Europe, les utilisateurs au Maroc et au Sénégal. Comment gérer proprement les fuseaux ?
MD,
        'answers' => [
            [
                'author' => 'Christian Mbarga', 'votes' => 5, 'accepted' => true, 'after' => 4,
                'body' => <<<'MD'
Règle d'or : **stocke en UTC, affiche dans le fuseau de l'utilisateur**.

1. Forcer la JVM et Hibernate en UTC :

```properties
spring.jpa.properties.hibernate.jdbc.time_zone=UTC
```

```java
@PostConstruct
void utc() {
    TimeZone.setDefault(TimeZone.getTimeZone("UTC"));
}
```

2. En base, utilise `timestamptz` et côté Java `Instant` ou `OffsetDateTime`, jamais `LocalDateTime` pour un instant précis.

3. Convertis à l'affichage : `instant.atZone(ZoneId.of("Africa/Casablanca"))`. Le Sénégal (`Africa/Dakar`) est en UTC toute l'année, le Maroc change selon les périodes : ne code jamais le décalage en dur.
MD,
            ],
            [
                'author' => 'Youssef El Amrani', 'votes' => 3, 'after' => 10,
                'body' => <<<'MD'
Je confirme pour le Maroc : entre l'heure légale et la suspension pendant le ramadan, un décalage codé en dur sera faux plusieurs fois par an. `ZoneId` avec une base de fuseaux à jour, et c'est réglé.
MD,
            ],
        ],
    ],
    [
        'author' => 'Aline Uwase', 'age' => 0.05, 'votes' => 3,
        'title' => 'Git : comment annuler mon dernier commit sans perdre mes changements ?',
        'tags' => ['Git'],
        'body' => <<<'MD'
J'ai fait un commit sur `main` au lieu de ma branche, et je ne l'ai pas encore poussé. Comment le défaire sans perdre mon travail ?
MD,
        'answers' => [
            [
                'author' => 'Jean-Paul Habimana', 'votes' => 4, 'accepted' => true, 'after' => 1,
                'body' => <<<'MD'
Puisque tu n'as rien poussé, c'est simple :

```bash
git reset --soft HEAD~1        # annule le commit, garde les changements indexés
git switch -c ma-branche       # crée ta branche avec ces changements
git commit -m "Ajoute le formulaire du club"
```

`--soft` garde tout. N'utilise **jamais** `--hard` dans ce cas : il effacerait tes modifications.

Si tu avais déjà poussé, il faudrait plutôt `git revert`, pour ne pas réécrire un historique que d'autres ont récupéré.
MD,
            ],
            [
                'author' => 'Nadège Dossou', 'votes' => 1, 'after' => 5,
                'body' => <<<'MD'
Ça m'est arrivé la semaine dernière ! Pour éviter que ça se reproduise, j'ai ajouté ma branche dans mon invite de commande (avec Oh My Zsh, c'est automatique).
MD,
            ],
        ],
    ],
    [
        'author' => 'Wanjiru Kamau', 'age' => 0.58, 'votes' => 9,
        'title' => 'Une seule interface pour Wave, Orange Money, MTN MoMo et M-Pesa ?',
        'tags' => ['Wave', 'Orange Money', 'MTN MoMo', 'Node.js'],
        'body' => <<<'MD'
Notre appli s'ouvre au Sénégal et en Côte d'Ivoire après le Kenya. Chaque opérateur a son API, ses statuts et ses callbacks. Comment vous organisez le code pour ne pas avoir des `if (operateur === …)` partout ?
MD,
        'answers' => [
            [
                'author' => 'Rodrigue Hounkpatin', 'votes' => 8, 'accepted' => true, 'after' => 5,
                'body' => <<<'MD'
Un **adaptateur par opérateur** derrière une interface commune, et un vocabulaire de statuts à toi.

```ts
export type StatutPaiement = 'en_attente' | 'reussi' | 'echoue';

export interface Operateur {
  demarrer(p: { montant: number; telephone: string; reference: string }): Promise<{ idExterne: string; urlRedirection?: string }>;
  statut(idExterne: string): Promise<StatutPaiement>;
}

const operateurs: Record<string, Operateur> = {
  wave: new WaveAdapter(),
  orange_money: new OrangeMoneyAdapter(),
  mtn_momo: new MomoAdapter(),
  mpesa: new MpesaAdapter(),
};

export const operateur = (code: string) => {
  const o = operateurs[code];
  if (!o) throw new Error(`Opérateur inconnu : ${code}`);
  return o;
};
```

Chaque adaptateur traduit les statuts de son API (`SUCCESSFUL`, `succeeded`, `SUCCESS`…) vers les tiens. Le reste de l'appli ne connaît que `reussi`. Ajouter un opérateur ne touche plus qu'un fichier.
MD,
            ],
            [
                'author' => 'Otieno Odhiambo', 'votes' => 3, 'after' => 12,
                'body' => <<<'MD'
Garde aussi la réponse brute de chaque opérateur en base (une colonne JSON). Le jour d'un litige avec un client, c'est la seule preuve qui compte.
MD,
            ],
            [
                'author' => 'Awa Sarr', 'votes' => 2, 'after' => 25,
                'body' => <<<'MD'
Côté interface, propose d'abord le moyen de paiement le plus utilisé dans le pays du client (Wave au Sénégal, Orange Money ou MTN en Côte d'Ivoire). Un menu de dix logos fait hésiter.
MD,
            ],
        ],
    ],
    [
        'author' => 'Patrick Kabongo', 'age' => 0.56, 'votes' => 5,
        'title' => 'Docker Compose pour développer en équipe avec Node.js et PostgreSQL',
        'tags' => ['Docker', 'Node.js', 'PostgreSQL'],
        'body' => <<<'MD'
Dans mon équipe à Kinshasa, chacun a une version différente de Node et de PostgreSQL, et « ça marche chez moi » est devenu notre phrase préférée. Un `docker-compose.yml` simple à proposer ?
MD,
        'answers' => [
            [
                'author' => 'Abdoul Kaboré', 'votes' => 6, 'accepted' => true, 'after' => 3,
                'body' => <<<'MD'
Voici une base qui marche bien chez nous :

```yaml
services:
  app:
    image: node:22-alpine
    working_dir: /app
    command: sh -c "npm ci && npm run dev"
    volumes:
      - .:/app
    ports:
      - "3000:3000"
    environment:
      DATABASE_URL: postgres://app:secret@db:5432/app
    depends_on:
      db:
        condition: service_healthy

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_USER: app
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: app
    volumes:
      - pgdata:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U app"]
      interval: 5s
      retries: 10

volumes:
  pgdata:
```

Astuce connexion lente : les images `alpine` sont 5 à 10 fois plus légères. Et faites un `docker compose pull` une fois au bureau, sur la fibre, plutôt que chacun chez soi.
MD,
            ],
            [
                'author' => 'Grâce Mukendi', 'votes' => 1, 'after' => 10,
                'body' => <<<'MD'
Merci, j'ai repris ce fichier pour un petit projet .NET en changeant juste l'image. Le `healthcheck` m'a évité des erreurs de connexion au démarrage.
MD,
            ],
        ],
    ],
    [
        'author' => 'Moussa Diop', 'age' => 0.54, 'votes' => 6,
        'title' => 'Combien facturer une intégration Wave pour une PME à Dakar ?',
        'tags' => ['Wave', 'Laravel'],
        'body' => <<<'MD'
Un client (une boutique de pièces auto) me demande d'ajouter le paiement Wave à son site Laravel existant : bouton de paiement, webhook, page de confirmation. Je ne sais jamais comment chiffrer ce type de mission. Vous partez sur quoi ?
MD,
        'answers' => [
            [
                'author' => 'Rodrigue Hounkpatin', 'votes' => 5, 'after' => 4,
                'body' => <<<'MD'
Je chiffre au temps passé, jamais « au feeling ». Pour ce périmètre, compte :

- lecture du code existant et des accès : une demi-journée ;
- intégration (session de paiement, webhook signé, statuts) : 1 à 2 jours ;
- tests en conditions réelles avec le client, petits paiements : une demi-journée ;
- documentation et passation : une demi-journée.

Soit 3 à 4 jours. Multiplie par ton tarif journalier, et précise noir sur blanc ce qui n'est **pas** inclus (remboursements, tableau de bord des transactions).
MD,
            ],
            [
                'author' => 'Koffi Mensah', 'votes' => 4, 'accepted' => true, 'after' => 9,
                'body' => <<<'MD'
D'accord avec Rodrigue sur la méthode. Deux ajouts qui évitent les mauvaises surprises :

1. **Fais payer un acompte** (30 à 50 %) avant de commencer, surtout pour un nouveau client.
2. **Propose une maintenance** séparée : les API de paiement évoluent, les certificats expirent. Un petit forfait mensuel, c'est un revenu régulier pour toi et de la sérénité pour lui.

Pour te situer sur les tarifs, regarde ce que demandent les devs avec ton niveau d'expérience dans ta ville : la page Le Pouls donnera bientôt les tarifs médians réels de la communauté.
MD,
            ],
        ],
    ],
    [
        'author' => 'Ama Owusu', 'age' => 0.52, 'votes' => 3,
        'title' => 'Flutter : afficher les montants en FCFA avec l\'espace comme séparateur',
        'tags' => ['Flutter', 'Dart'],
        'body' => <<<'MD'
Je dois afficher des prix comme `25 000 FCFA` dans mon appli. `toString()` donne `25000`. Quelle est la bonne façon ?
MD,
        'answers' => [
            [
                'author' => 'Aïssatou Koné', 'votes' => 5, 'accepted' => true, 'after' => 2,
                'body' => <<<'MD'
Utilise le paquet `intl` avec la locale française : il met l'espace (insécable) entre les milliers.

```dart
import 'package:intl/intl.dart';

final _fcfa = NumberFormat.decimalPattern('fr');

String formatFcfa(int montant) => '${_fcfa.format(montant)} FCFA';

formatFcfa(25000);   // '25 000 FCFA'
formatFcfa(1250000); // '1 250 000 FCFA'
```

N'utilise pas de `double` pour les montants : le FCFA n'a pas de centimes, garde des entiers.
MD,
            ],
            [
                'author' => 'Rasmata Ouédraogo', 'votes' => 2, 'after' => 7,
                'body' => <<<'MD'
Pense à charger les données de la locale si tu formates des dates aussi : `await initializeDateFormatting('fr');` au démarrage, sinon tu auras une exception.
MD,
            ],
        ],
    ],
    [
        'author' => 'Kwame Asante', 'age' => 0.5, 'votes' => 4,
        'title' => 'Laravel Sanctum : erreur CORS entre mon interface Vue et l\'API',
        'tags' => ['Laravel', 'Vue.js'],
        'body' => <<<'MD'
Mon interface Vue tourne sur `app.monsite.com` et l'API Laravel sur `api.monsite.com`. La connexion renvoie une erreur CORS puis un 419. J'utilise Sanctum en mode SPA.
MD,
        'answers' => [
            [
                'author' => 'Yao Kouassi', 'votes' => 5, 'accepted' => true, 'after' => 3,
                'body' => <<<'MD'
En mode SPA, Sanctum utilise des cookies : il faut que tout soit aligné.

`.env` de l'API :

```ini
SESSION_DOMAIN=.monsite.com
SANCTUM_STATEFUL_DOMAINS=app.monsite.com
```

`config/cors.php` :

```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
'allowed_origins' => ['https://app.monsite.com'],
'supports_credentials' => true,
```

Côté Vue, envoie les cookies et récupère le jeton CSRF avant la connexion :

```js
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

await axios.get('https://api.monsite.com/sanctum/csrf-cookie');
await axios.post('https://api.monsite.com/login', identifiants);
```

Le 419 vient presque toujours de cet appel `csrf-cookie` oublié.
MD,
            ],
        ],
    ],
    [
        'author' => 'Otieno Odhiambo', 'age' => 0.48, 'votes' => 3,
        'title' => 'Sauvegarder une base PostgreSQL chaque nuit sur un petit serveur',
        'tags' => ['PostgreSQL', 'Linux'],
        'body' => <<<'MD'
Notre plateforme de micro-crédit tourne sur un seul serveur. Je veux une sauvegarde automatique chaque nuit, gardée 14 jours, et une copie ailleurs. Quelle est la méthode la plus simple et fiable ?
MD,
        'answers' => [
            [
                'author' => 'Abdoul Kaboré', 'votes' => 6, 'accepted' => true, 'after' => 5,
                'body' => <<<'MD'
Un script `pg_dump` en format personnalisé (compressé, restauration sélective possible) et un timer systemd ou cron :

```bash
#!/usr/bin/env bash
set -euo pipefail

DEST=/var/backups/postgres
FICHIER="$DEST/credit-$(date +%F).dump"

pg_dump --format=custom --dbname=credit --file="$FICHIER"
find "$DEST" -name '*.dump' -mtime +14 -delete

# Copie hors du serveur (autre machine, stockage objet…)
rclone copy "$FICHIER" sauvegardes:credit/
```

```cron
30 2 * * * postgres /usr/local/bin/sauvegarde-pg.sh
```

Et surtout : **teste la restauration** une fois par mois sur une autre machine avec `pg_restore`. Une sauvegarde jamais restaurée n'est pas une sauvegarde.
MD,
            ],
            [
                'author' => 'Christian Mbarga', 'votes' => 2, 'after' => 14,
                'body' => <<<'MD'
Pour une base qui grossit vite, regarde aussi `pgBackRest` : sauvegardes incrémentales et restauration à un instant précis. Mais pour commencer, le script d'Abdoul suffit largement.
MD,
            ],
        ],
    ],
    [
        'author' => 'Sitsofe Amouzou', 'age' => 0.46, 'votes' => 5,
        'title' => 'Livewire : ma recherche envoie une requête à chaque lettre tapée',
        'tags' => ['Livewire', 'Laravel', '3G'],
        'body' => <<<'MD'
Sur mon annuaire d'élèves, le champ de recherche est lié avec `wire:model.live`. En 3G, l'interface devient très lente : chaque lettre part au serveur. Comment limiter ça ?
MD,
        'answers' => [
            [
                'author' => 'Moussa Diop', 'votes' => 6, 'accepted' => true, 'after' => 2,
                'body' => <<<'MD'
Ajoute un délai : la requête ne part que lorsque l'utilisateur s'arrête de taper.

```blade
<input type="search" wire:model.live.debounce.400ms="recherche" placeholder="Nom de l'élève">
```

400 ms est un bon compromis. Pour les autres champs (listes déroulantes, cases à cocher), `wire:model.live` sans délai convient, il n'y a qu'un changement.

Et côté serveur, pagine les résultats : une recherche qui renvoie 2 000 élèves est lente même avec un seul appel.
MD,
            ],
            [
                'author' => 'Rodrigue Hounkpatin', 'votes' => 2, 'after' => 6,
                'body' => <<<'MD'
Ajoute aussi `wire:loading` pour montrer que ça cherche : en 3G, sans retour visuel, les gens retapent et relancent des requêtes.
MD,
            ],
        ],
    ],
    [
        'author' => 'Amine Trabelsi', 'age' => 0.44, 'votes' => 4,
        'title' => 'Données vocales en wolof et en bambara : par où commencer ?',
        'tags' => ['Machine learning', 'Python'],
        'body' => <<<'MD'
Je travaille sur la reconnaissance vocale en darija. Je voudrais l'étendre au wolof et au bambara, mais je ne trouve presque pas de données. Comment vous feriez pour constituer un petit corpus de qualité ?
MD,
        'answers' => [
            [
                'author' => 'Mariam Traoré', 'votes' => 3, 'after' => 8,
                'body' => <<<'MD'
Je parle bambara et je serais partante pour aider. Quelques pistes :

- La **radio locale** : beaucoup d'émissions sont en bambara, mais demande toujours l'autorisation avant d'utiliser un enregistrement.
- Une collecte **avec les gens** : une petite appli où des volontaires lisent des phrases courtes. On a fait ça avec l'université de Bamako pour un autre projet.
- Commencer par un domaine précis (chiffres, montants, noms de villes) plutôt que la langue entière : c'est déjà très utile pour un service USSD ou vocal.
MD,
            ],
            [
                'author' => 'Fatou Ndiaye', 'votes' => 2, 'after' => 12,
                'body' => <<<'MD'
Même chose pour le wolof : je peux relire des transcriptions. Attention à l'orthographe, il y a plusieurs façons d'écrire le wolof ; choisis une norme (celle du CLAD par exemple) dès le départ, sinon ton corpus sera incohérent.
MD,
            ],
        ],
    ],
    [
        'author' => 'Léa Moussavou', 'age' => 0.42, 'votes' => 3,
        'title' => 'Symfony : tester un service qui appelle une API externe',
        'tags' => ['Symfony', 'PHP'],
        'body' => <<<'MD'
J'ai un service qui interroge l'API d'un opérateur pour vérifier un paiement. Mes tests appellent la vraie API, ils sont lents et échouent dès que le réseau tousse. Comment faire proprement ?
MD,
        'answers' => [
            [
                'author' => 'Rodrigue Hounkpatin', 'votes' => 4, 'accepted' => true, 'after' => 6,
                'body' => <<<'MD'
Injecte le `HttpClientInterface` dans ton service, puis donne-lui un `MockHttpClient` dans les tests :

```php
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

public function test_paiement_reussi(): void
{
    $client = new MockHttpClient([
        new MockResponse(json_encode(['status' => 'SUCCESSFUL']), ['http_code' => 200]),
    ]);

    $verificateur = new VerificateurPaiement($client);

    $this->assertTrue($verificateur->estPaye('CMD-1042'));
}
```

Tes tests deviennent instantanés et tu peux simuler les cas difficiles : `500`, délai dépassé, réponse inattendue. Garde un ou deux tests d'intégration contre la sandbox, lancés à part.
MD,
            ],
        ],
    ],
    [
        'author' => 'Ousmane Issoufou', 'age' => 0.4, 'votes' => 5,
        'title' => 'Envoyer des alertes par SMS à 2 000 éleveurs sans tout bloquer',
        'tags' => ['SMS', 'PHP', 'MySQL'],
        'body' => <<<'MD'
Je dois envoyer une alerte météo par SMS à 2 000 éleveurs. Ma boucle PHP s'arrête au bout de 30 secondes (limite d'exécution) et certains reçoivent le message deux fois quand je relance.
MD,
        'answers' => [
            [
                'author' => 'Koffi Mensah', 'votes' => 6, 'accepted' => true, 'after' => 3,
                'body' => <<<'MD'
Deux problèmes, deux solutions.

**Ne fais pas l'envoi dans la requête web.** Crée une table `envois` (un message par destinataire, avec un statut), et un script en ligne de commande lancé par cron qui traite les envois en attente par lots :

```php
$lot = $pdo->query("SELECT id, telephone, message FROM envois WHERE statut = 'en_attente' LIMIT 100")->fetchAll();

foreach ($lot as $envoi) {
    $pdo->prepare("UPDATE envois SET statut = 'en_cours' WHERE id = ? AND statut = 'en_attente'")->execute([$envoi['id']]);
    // envoi via l'API SMS…
    $pdo->prepare("UPDATE envois SET statut = 'envoye', envoye_le = NOW() WHERE id = ?")->execute([$envoi['id']]);
}
```

**Les doublons disparaissent** parce que chaque ligne change de statut : relancer le script ne reprend que ce qui n'est pas parti.

Ensuite, regarde l'envoi groupé de ton fournisseur : beaucoup acceptent plusieurs destinataires en un seul appel.
MD,
            ],
            [
                'author' => 'Chinedu Okafor', 'votes' => 2, 'after' => 9,
                'body' => <<<'MD'
Respecte aussi la limite de débit du fournisseur (souvent quelques dizaines de messages par seconde) : un petit `usleep` entre les lots évite de te faire bloquer.
MD,
            ],
        ],
    ],
    [
        'author' => 'Aisha Bello', 'age' => 0.38, 'votes' => 4,
        'title' => 'Comment trouver des coéquipiers dans un autre pays pour un projet agricole ?',
        'tags' => ['Django', 'Open source'],
        'body' => <<<'MD'
Je construis une plateforme qui relie des producteurs de mil du Nigeria et du Niger. J'ai besoin d'un dev au Niger qui connaît le terrain et parle haoussa. Comment vous organisez une petite équipe à distance entre deux pays, avec des connexions inégales ?
MD,
        'answers' => [
            [
                'author' => 'Patrick Kabongo', 'votes' => 3, 'after' => 4,
                'body' => <<<'MD'
Ce qui marche dans nos équipes réparties :

- **L'écrit d'abord** : les tickets et les décisions dans un outil partagé, pas dans des appels vidéo que la moitié de l'équipe ne peut pas suivre en 3G.
- **Un point court par semaine**, en audio seulement.
- **Des tâches autonomes** : chacun peut avancer une journée sans attendre l'autre.
- **Un dépôt public** si possible : ça attire des contributeurs et rassure les partenaires.
MD,
            ],
            [
                'author' => 'Ousmane Issoufou', 'votes' => 4, 'accepted' => true, 'after' => 7,
                'body' => <<<'MD'
Je suis à Niamey, je parle haoussa et je travaille déjà avec des éleveurs : ton projet m'intéresse. On peut en discuter ?

Pour l'organisation, j'ajoute un point aux conseils de Patrick : **testez l'appli sur le terrain dès le premier mois**. Ce qui marche à Abuja sur un bon téléphone ne marche pas forcément dans un village avec un réseau EDGE.
MD,
            ],
        ],
    ],
    [
        'author' => 'Boubacar Keïta', 'age' => 0.36, 'votes' => 3,
        'title' => 'USSD : comment sécuriser un menu qui consulte un solde bancaire ?',
        'tags' => ['USSD', 'Java', 'Cybersécurité'],
        'body' => <<<'MD'
Notre microfinance veut proposer la consultation de solde par USSD. Le numéro de téléphone suffit-il à identifier le client ? Quelles protections minimales vous recommandez ?
MD,
        'answers' => [
            [
                'author' => 'Christian Mbarga', 'votes' => 5, 'accepted' => true, 'after' => 5,
                'body' => <<<'MD'
Le numéro transmis par la passerelle USSD de l'opérateur est fiable (il vient du réseau, pas de l'utilisateur), mais il ne suffit pas : un téléphone se prête ou se vole. Le minimum :

1. **Un code PIN** demandé à chaque session, stocké haché (bcrypt ou argon2), jamais en clair.
2. **Un blocage** après 3 erreurs, avec déblocage en agence.
3. **N'accepter les appels que des IP de la passerelle** de l'opérateur ou de l'agrégateur.
4. **Ne jamais afficher plus que nécessaire** : le solde, pas l'historique complet.
5. **Journaliser** chaque consultation (numéro, heure, résultat) pour les audits.

Et gère la fin de session : si l'utilisateur ne répond pas, la session expire côté opérateur, ton serveur ne doit rien garder d'ouvert.
MD,
            ],
            [
                'author' => 'Koffi Mensah', 'votes' => 3, 'after' => 11,
                'body' => <<<'MD'
Attention au PIN dans le champ `text` : chez certains agrégateurs, tout le parcours de la session est renvoyé à chaque étape, PIN compris. Ne journalise jamais ce champ tel quel dans tes logs.
MD,
            ],
        ],
    ],
    [
        'author' => 'Adjoua N\'Guessan', 'age' => 0.3, 'votes' => 3,
        'title' => 'Tailwind CSS : comment gérer un mode sombre sans clignotement ?',
        'tags' => ['Tailwind CSS', 'JavaScript'],
        'body' => <<<'MD'
J'ai ajouté un bouton clair/sombre avec Tailwind. Au chargement de chaque page, on voit un flash blanc avant que le thème sombre s'applique. Comment l'éviter ?
MD,
        'answers' => [
            [
                'author' => 'Fatou Ndiaye', 'votes' => 4, 'accepted' => true, 'after' => 2,
                'body' => <<<'MD'
Le flash vient du fait que ton script s'exécute après le premier affichage. Mets un **petit script en ligne dans le `<head>`**, avant ta feuille de style :

```html
<script>
  (function () {
    var theme = null;
    try { theme = localStorage.getItem('theme'); } catch (e) {}
    if (theme === 'dark' || (theme === null && matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    }
  })();
</script>
```

Avec Tailwind 4, déclare la variante basée sur la classe :

```css
@custom-variant dark (&:where(.dark, .dark *));
```

Le `try` protège la navigation privée de certains navigateurs, où `localStorage` peut lever une exception.
MD,
            ],
        ],
    ],
    [
        'author' => 'Aïssatou Koné', 'age' => 0.03, 'votes' => 2,
        'title' => 'Hackathon : comment préparer une démo qui marche même sans Internet ?',
        'tags' => ['Flutter', 'Hors ligne'],
        'body' => <<<'MD'
On prépare un hackathon à Abidjan le mois prochain. L'an dernier, le Wi-Fi de la salle est tombé pendant notre démo. Comment vous préparez une démo robuste ?
MD,
        'answers' => [
            [
                'author' => 'Patrick Kabongo', 'votes' => 2, 'after' => 3,
                'body' => <<<'MD'
Notre liste avant chaque démo :

- Une **version de démo avec des données locales** (un faux serveur ou des réponses enregistrées), activable par un réglage.
- La démo **enregistrée en vidéo** la veille, en secours.
- Le **partage de connexion** d'un téléphone avec un forfait chargé, testé avant de monter sur scène.
- Le parcours **répété trois fois**, chronométré.
MD,
            ],
        ],
    ],
    [
        'author' => 'Nadège Dossou', 'age' => 0.02, 'votes' => 1,
        'title' => 'Next.js : ma variable d\'environnement est undefined dans le navigateur',
        'tags' => ['Next.js', 'React'],
        'body' => <<<'MD'
J'ai mis `API_URL=https://api.monprojet.com` dans `.env.local`. Côté serveur, `process.env.API_URL` fonctionne, mais dans un composant client j'obtiens `undefined`. J'ai bien relancé `npm run dev`.
MD,
        'answers' => [],
    ],
    [
        'author' => 'Cheikh Fall', 'age' => 0.015, 'votes' => 0,
        'title' => 'Quel téléphone Android d\'entrée de gamme pour tester mes applis ?',
        'tags' => ['Android', 'Flutter', '3G'],
        'body' => <<<'MD'
Mes utilisateurs (des pêcheurs de Ziguinchor) ont des téléphones à 2 Go de RAM. Je développe sur un émulateur rapide et je n'ai aucune idée de ce que ça donne chez eux. Quel modèle pas cher vous utilisez pour tester ?
MD,
        'answers' => [],
    ],
    [
        'author' => 'Kwame Asante', 'age' => 0.01, 'votes' => 2,
        'title' => 'Laravel : comment gérer les montants en FCFA et en cedi dans la même appli ?',
        'tags' => ['Laravel', 'PHP', 'MTN MoMo'],
        'body' => <<<'MD'
Notre appli de logistique s'ouvre au Togo. Au Ghana on facture en cedis (avec des pesewas), au Togo en FCFA (sans centimes). Comment stocker et afficher les deux proprement ? Entiers, décimaux, une table des devises ?
MD,
        'answers' => [],
    ],
];
