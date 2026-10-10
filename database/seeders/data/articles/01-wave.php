<?php

return [
    'author' => 'Moussa Diop', 'category' => 'tutorial', 'age' => 0.92, 'useful' => 14,
    'title' => 'Intégrer l\'API Wave de bout en bout dans Laravel',
    'tags' => ['Wave', 'Laravel', 'PHP'],
    'body' => <<<'MD'
Au Sénégal, proposer Wave sur un site marchand n'est plus une option : c'est souvent le moyen de paiement que les clients ont déjà dans la poche. Bonne nouvelle, l'API Checkout de Wave est simple. Mauvaise nouvelle, les détails qui font la différence entre une démo et une intégration fiable ne sont pas tous dans la documentation.

Ce tutoriel couvre le parcours complet dans Laravel : créer une session de paiement, rediriger le client, recevoir le webhook signé, et ne jamais livrer une commande non payée.

## Le parcours en une phrase

Ton serveur crée une **session de paiement** chez Wave, le client est redirigé vers la page Wave pour valider, puis Wave prévient ton serveur par un **webhook**. La page de retour n'est qu'une politesse : seul le webhook vérifié fait foi.

## 1. Configurer les accès

Dans le tableau de bord Wave Business, crée une clé d'API et un secret de webhook. Range-les dans `.env`, jamais dans le code :

```ini
WAVE_API_KEY=wave_sn_prod_xxxxxxxx
WAVE_WEBHOOK_SECRET=wave_sn_WHS_xxxxxxxx
```

Puis dans `config/services.php` :

```php
'wave' => [
    'key' => env('WAVE_API_KEY'),
    'webhook_secret' => env('WAVE_WEBHOOK_SECRET'),
],
```

## 2. Créer la session de paiement

Une petite classe dédiée garde le contrôleur lisible et se remplace facilement dans les tests :

```php
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class WaveCheckout
{
    public function createSession(Order $order): array
    {
        return Http::withToken(config('services.wave.key'))
            ->timeout(15)
            ->retry(2, 500)
            ->post('https://api.wave.com/v1/checkout/sessions', [
                'amount' => (string) $order->amount_fcfa,
                'currency' => 'XOF',
                'client_reference' => $order->reference,
                'success_url' => route('orders.thanks', $order),
                'error_url' => route('orders.failed', $order),
            ])
            ->throw()
            ->json();
    }
}
```

Trois remarques :

- Le montant est une **chaîne**, et le franc CFA n'a pas de centimes : envoie `"15000"`, jamais `"15000.00"`.
- `client_reference` est ta référence de commande. Tu la retrouveras dans le webhook : c'est le lien entre les deux mondes.
- `retry(2, 500)` rattrape les micro-coupures, fréquentes quand le serveur est loin de l'API.

Dans le contrôleur, on enregistre l'identifiant de session puis on redirige :

```php
public function pay(Order $order, WaveCheckout $wave)
{
    abort_unless($order->isPending(), 409);

    $session = $wave->createSession($order);
    $order->update(['wave_session_id' => $session['id']]);

    return redirect()->away($session['wave_launch_url']);
}
```

Sur mobile, `wave_launch_url` ouvre directement l'application Wave si elle est installée. C'est un vrai plus pour le taux de conversion.

## 3. Recevoir le webhook

Wave appelle ton URL de webhook à chaque changement d'état. Elle doit être publique, en HTTPS, et exclue de la protection CSRF :

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['webhooks/wave']);
})
```

### Vérifier la signature

Sans vérification, n'importe qui peut appeler ton URL et marquer une commande comme payée. Wave signe chaque appel dans l'en-tête `Wave-Signature`, sous la forme `t=<timestamp>,v1=<signature>`. La signature est un HMAC-SHA256 du timestamp suivi du corps brut de la requête.

```php
public function handle(Request $request, Closure $next)
{
    parse_str(str_replace(',', '&', (string) $request->header('Wave-Signature')), $parts);

    $timestamp = $parts['t'] ?? '';
    $expected = hash_hmac('sha256', $timestamp.$request->getContent(), config('services.wave.webhook_secret'));

    abort_unless(hash_equals($expected, $parts['v1'] ?? '') && abs(time() - (int) $timestamp) < 300, 401);

    return $next($request);
}
```

Utilise toujours `getContent()`, le corps **brut**. Décoder puis réencoder le JSON change les espaces et casse la signature.

### Traiter l'événement une seule fois

Wave peut renvoyer le même événement si ton serveur a répondu trop lentement. Ton traitement doit être **idempotent** :

```php
public function __invoke(Request $request)
{
    $event = $request->json()->all();

    $isNew = WaveEvent::query()->insertOrIgnore([
        'event_id' => $event['id'],
        'type' => $event['type'],
        'created_at' => now(),
    ]) === 1;

    if ($isNew && $event['type'] === 'checkout.session.completed') {
        ConfirmOrder::dispatch($event['data']['client_reference'], $event['data']['id']);
    }

    return response()->noContent();
}
```

La contrainte unique sur `event_id` fait le travail : un doublon est simplement ignoré. Le traitement lourd (facture, e-mail, SMS) part dans une file d'attente, et Wave reçoit sa réponse en quelques millisecondes.

## 4. Confirmer la commande

Le job vérifie que le montant payé correspond bien à la commande avant de la valider :

```php
public function handle(): void
{
    $order = Order::where('reference', $this->reference)->firstOrFail();

    if ($order->isPaid()) {
        return; // déjà fait : rien à refaire
    }

    $order->markAsPaid($this->sessionId);
    $order->customer->notify(new OrderPaid($order));
}
```

## 5. La page de retour

Quand le client revient sur `success_url`, le webhook n'est peut-être pas encore arrivé. Affiche donc un état d'attente honnête : « Paiement en cours de confirmation », avec un rafraîchissement automatique toutes les quelques secondes. Ne marque jamais la commande payée sur cette seule page : un client peut y arriver sans avoir payé.

## Tester sans dépenser

- En local, expose ton serveur avec un tunnel HTTPS et déclare cette URL comme webhook de test.
- Dans tes tests automatiques, remplace `WaveCheckout` par un faux, et envoie à ton contrôleur de webhook un corps signé avec un secret de test : tu vérifies ainsi la signature, l'idempotence et la confirmation sans réseau.
- Avant la mise en production, fais deux ou trois vrais paiements de 100 FCFA. C'est le test le plus parlant.

## En résumé

1. Les secrets dans `.env`, le montant en chaîne sans décimales.
2. La session de paiement dans un service dédié, avec un `client_reference`.
3. Un webhook signé, vérifié sur le corps brut, idempotent.
4. Le traitement lourd en file d'attente.
5. Une page de retour qui attend le webhook au lieu de le remplacer.

Avec ces cinq points, ton intégration Wave tiendra le jour où tout le monde paie en même temps, comme un soir de Tabaski.
MD,
];
