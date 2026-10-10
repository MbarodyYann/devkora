<?php

return [
    'author' => 'Christian Mbarga', 'category' => 'tutorial', 'age' => 0.5, 'useful' => 8,
    'title' => 'Un menu USSD avec Africa\'s Talking, de zéro à la production',
    'tags' => ['USSD', 'PHP', 'Laravel'],
    'body' => <<<'MD'
Le USSD, ce sont ces codes comme `*144#` qu'on compose pour consulter son crédit. Il fonctionne sur **tous les téléphones**, même les plus simples, sans Internet ni application à installer. Pour toucher les utilisateurs qui n'ont pas de smartphone, c'est souvent le meilleur canal. J'ai conçu des services USSD pendant huit ans dans les télécoms ; voici comment en construire un avec Africa's Talking et Laravel.

## Comment ça marche

1. L'utilisateur compose ton code (par exemple `*384*123#`).
2. L'opérateur transmet la session à Africa's Talking, qui appelle **ton URL** en POST.
3. Ton serveur répond en texte brut :
   - `CON …` pour afficher un écran et attendre une saisie ;
   - `END …` pour afficher un dernier message et fermer la session.
4. À chaque saisie, Africa's Talking rappelle ton URL.

La requête contient notamment `sessionId`, `serviceCode`, `phoneNumber` et `text`.

## Le champ `text` : tout le parcours

C'est la clé de tout. `text` contient **toutes les saisies de la session**, séparées par des `*` :

| Étape | `text` reçu |
|---|---|
| Premier appel | `""` (vide) |
| L'utilisateur tape 1 | `"1"` |
| Puis 5000 | `"1*5000"` |
| Puis 1 pour confirmer | `"1*5000*1"` |

Pas besoin de session côté serveur pour savoir où on en est : on découpe `text` et on lit le chemin.

## Un premier menu dans Laravel

La route, exclue de la protection CSRF :

```php
Route::post('/ussd', UssdController::class)->middleware('throttle:ussd');
```

Le contrôleur :

```php
<?php

namespace App\Http\Controllers;

use App\Models\Membre;
use Illuminate\Http\Request;

class UssdController
{
    public function __invoke(Request $request)
    {
        $etapes = $request->input('text') === '' ? [] : explode('*', $request->input('text'));
        $membre = Membre::firstWhere('telephone', $request->input('phoneNumber'));

        $reponse = match (true) {
            $membre === null => 'END Ce numéro n\'est pas inscrit à la tontine.',
            $etapes === [] => "CON Tontine Mvog-Ada\n1. Cotiser\n2. Mon solde\n3. Prochain tour",
            $etapes === ['1'] => 'CON Montant à cotiser (FCFA) :',
            $etapes[0] === '1' && count($etapes) === 2 => $this->confirmer((int) $etapes[1]),
            $etapes[0] === '1' && count($etapes) === 3 => $this->cotiser($membre, (int) $etapes[1], $etapes[2]),
            $etapes === ['2'] => 'END Votre solde : '.number_format($membre->solde, 0, ',', ' ').' FCFA',
            $etapes === ['3'] => 'END Prochain tour : '.$membre->tontine->prochainBeneficiaire(),
            default => 'END Choix invalide. Recomposez le code.',
        };

        return response($reponse)->header('Content-Type', 'text/plain');
    }

    private function confirmer(int $montant): string
    {
        if ($montant < 500) {
            return 'END Le montant minimum est de 500 FCFA.';
        }

        return "CON Cotiser $montant FCFA ?\n1. Confirmer\n2. Annuler";
    }

    private function cotiser(Membre $membre, int $montant, string $choix): string
    {
        if ($choix !== '1') {
            return 'END Opération annulée.';
        }

        EnregistrerCotisation::dispatch($membre->id, $montant);

        return 'END Merci ! Vous recevrez une confirmation par SMS.';
    }
}
```

Remarque le dernier écran : le vrai travail (débit Mobile Money, enregistrement, SMS) part dans une **file d'attente**. La session USSD a un délai court, et l'utilisateur ne doit pas attendre une API de paiement lente.

## Les contraintes à respecter

**La longueur des écrans.** Beaucoup de téléphones coupent au-delà d'environ 160 caractères. Des menus courts, des mots simples, pas plus de cinq choix par écran.

**Le délai.** L'opérateur ferme la session si l'utilisateur met trop de temps à répondre (souvent une vingtaine de secondes par écran), et si ton serveur répond trop lentement. Vise moins d'une seconde par réponse : pas de requête lourde, pas d'appel externe pendant la session.

**La langue.** Pense à tes utilisateurs : un menu en français simple, ou dans la langue locale si ton public la lit plus facilement.

**Les accents.** Selon les opérateurs, les caractères accentués passent mal. Teste sur plusieurs réseaux, et prévois une version sans accents si besoin.

## La sécurité

Le numéro de téléphone transmis par la passerelle vient du réseau : il est fiable. Mais un téléphone se prête ou se vole. Pour toute action sensible :

- demande un **code PIN**, stocké haché, jamais en clair ;
- bloque le compte après trois erreurs ;
- **n'accepte les requêtes que des adresses IP d'Africa's Talking** ;
- et ne journalise jamais le champ `text` tel quel : il contient le PIN.

## Tester

Africa's Talking propose un **simulateur** dans son environnement de test : tu composes ton code dans le navigateur et tu parcours ton menu, sans téléphone ni crédit. Écris aussi un test automatique par chemin :

```php
test('le parcours de cotisation demande une confirmation', function () {
    Membre::factory()->create(['telephone' => '+237699123456']);

    $this->post('/ussd', ['text' => '1*5000', 'phoneNumber' => '+237699123456', 'sessionId' => 'abc'])
        ->assertSee("CON Cotiser 5000 FCFA ?\n1. Confirmer\n2. Annuler", false);
});
```

## Passer en production

Il faut demander un **code USSD** (dédié ou partagé) auprès d'Africa's Talking ou d'un agrégateur local, pour chaque pays et chaque opérateur visés. Les délais d'attribution peuvent prendre plusieurs semaines : lance la démarche tôt, pendant que tu développes sur le simulateur.

Le USSD n'est pas une technologie à la mode. Mais c'est l'une des rares qui touche tout le monde, du commerçant avec son téléphone à clapet au cadre avec son smartphone. Pour beaucoup de services en Afrique, c'est un très bon point de départ.
MD,
];
