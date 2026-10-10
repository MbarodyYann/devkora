<?php

return [
    'author' => 'Mamadou Bah', 'category' => 'tutorial', 'age' => 0.78, 'useful' => 9,
    'title' => 'Tester Orange Money en sandbox sans perdre une journée',
    'tags' => ['Orange Money', 'PHP', 'API'],
    'body' => <<<'MD'
La première fois que j'ai intégré Orange Money Web Payment pour un site marchand de Conakry, j'ai perdu une journée entière sur des erreurs que personne n'expliquait. Ce guide rassemble ce que j'aurais aimé lire ce jour-là : la configuration de l'environnement de test, le parcours complet d'un paiement, et les pièges qui font perdre du temps.

## Ce qu'il te faut avant de coder

Sur le portail développeur d'Orange, crée une application et abonne-la à l'API **Orange Money Web Payment** de ton pays. Tu obtiens :

- un **client ID** et un **client secret**, pour obtenir un jeton OAuth ;
- un **merchant key**, qui identifie ton compte marchand auprès de l'API de paiement.

En environnement de développement, l'API répond comme en production, mais aucun argent ne circule.

## Le piège numéro un : la devise

En développement, la devise n'est ni `XOF`, ni `GNF`, ni `XAF` : c'est **`OUV`**. Tout autre code de devise renvoie une erreur peu parlante. Tu passeras à la devise réelle avec les identifiants de production. Mets donc la devise dans la configuration, jamais en dur :

```ini
ORANGE_MONEY_CURRENCY=OUV
ORANGE_MONEY_ENV=dev
```

## Étape 1 : obtenir un jeton

L'API utilise OAuth 2 en mode `client_credentials`. Le jeton dure environ une heure : garde-le en cache au lieu d'en redemander un à chaque paiement.

```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function orangeToken(): string
{
    return Cache::remember('orange_money.token', now()->addMinutes(50), function () {
        $basic = base64_encode(config('services.orange.client_id').':'.config('services.orange.client_secret'));

        return Http::asForm()
            ->withHeaders(['Authorization' => "Basic $basic"])
            ->post('https://api.orange.com/oauth/v3/token', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');
    });
}
```

Cinquante minutes de cache pour un jeton qui en dure soixante : on garde une marge, et on n'utilise jamais un jeton sur le point d'expirer.

## Étape 2 : créer le paiement

```php
$env = config('services.orange.env'); // « dev » en test

$payment = Http::withToken(orangeToken())
    ->post("https://api.orange.com/orange-money-webpay/$env/v1/webpayment", [
        'merchant_key' => config('services.orange.merchant_key'),
        'currency' => config('services.orange.currency'),
        'order_id' => $commande->reference,
        'amount' => $commande->montant,
        'return_url' => route('paiement.retour', $commande),
        'cancel_url' => route('paiement.annule', $commande),
        'notif_url' => route('webhooks.orange'),
        'lang' => 'fr',
        'reference' => 'Boutique Kaloum',
    ])
    ->throw()
    ->json();

$commande->update([
    'om_pay_token' => $payment['pay_token'],
    'om_notif_token' => $payment['notif_token'],
]);

return redirect()->away($payment['payment_url']);
```

La réponse contient trois valeurs importantes :

- `payment_url` : la page Orange Money où tu rediriges le client ;
- `pay_token` : l'identifiant du paiement, pour en demander le statut ;
- `notif_token` : le jeton qui reviendra dans la notification, pour prouver qu'elle vient bien d'Orange.

**Garde-les tous les deux en base.** Sans `notif_token`, tu ne peux pas vérifier la notification.

## Étape 3 : recevoir la notification

Orange appelle ton `notif_url` avec le statut du paiement. Vérifie que le `notif_token` reçu correspond à celui de la commande, et ne fais confiance qu'à cette notification :

```php
public function __invoke(Request $request)
{
    $commande = Commande::where('om_notif_token', $request->input('notif_token'))->first();

    if (! $commande) {
        return response()->noContent(); // inconnue : on ignore, sans rien révéler
    }

    if ($request->input('status') === 'SUCCESS' && ! $commande->estPayee()) {
        $commande->marquerPayee($request->input('txnid'));
    }

    return response()->noContent();
}
```

Comme pour tout webhook, exclue cette route de la protection CSRF et rends le traitement idempotent : une deuxième notification identique ne doit rien changer.

## Étape 4 : vérifier le statut en cas de doute

Les notifications se perdent parfois, surtout pendant les coupures. Une tâche planifiée qui interroge le statut des paiements restés en attente règle le problème :

```php
$statut = Http::withToken(orangeToken())
    ->post("https://api.orange.com/orange-money-webpay/$env/v1/transactionstatus", [
        'order_id' => $commande->reference,
        'amount' => $commande->montant,
        'pay_token' => $commande->om_pay_token,
    ])
    ->json('status'); // INITIATED, PENDING, SUCCESS, FAILED ou EXPIRED
```

Lance-la toutes les cinq minutes pour les paiements de plus de dix minutes.

## Les pièges qui m'ont coûté du temps

1. **La devise `OUV`** en développement, déjà dit, mais c'est le premier piège de tout le monde.
2. **Le `order_id` doit être unique** : réessayer un paiement avec la même référence échoue. Ajoute un suffixe à chaque tentative (`CMD-1042-2`).
3. **Les URL de retour doivent être publiques** et en HTTPS. `localhost` ne marche pas : utilise un tunnel en développement.
4. **La page de retour n'est pas une preuve de paiement.** Un client peut y revenir sans avoir payé. Attends la notification.
5. **Le montant est un entier.** Pas de décimales en franc guinéen ni en franc CFA.

## Pour finir

Orange Money a la réputation d'être difficile à intégrer. En réalité, l'API est cohérente une fois qu'on connaît ses conventions : la devise de test, les trois jetons à garder, et la notification comme seule source de vérité. Avec ce guide, il te faut une matinée, pas une semaine.
MD,
];
