<?php

return [
    'author' => 'Wanjiru Kamau', 'category' => 'best_practice', 'age' => 0.2, 'useful' => 10,
    'title' => 'Sécuriser une API qui touche au Mobile Money : la liste de contrôle',
    'tags' => ['Cybersécurité', 'API', 'MTN MoMo', 'Wave'],
    'body' => <<<'MD'
Depuis cinq ans, j'intègre M-Pesa à Nairobi, et depuis peu Wave et MTN MoMo pour une ouverture en Afrique de l'Ouest. Les API changent d'un opérateur à l'autre, mais les attaques sont toujours les mêmes : faux webhooks, montants modifiés, rejeu de requêtes, clés qui fuient. Voici la liste de contrôle que nous appliquons avant toute mise en production.

## 1. Les secrets ne quittent jamais le serveur

- Les clés d'API et les secrets de webhook vivent dans les **variables d'environnement**, jamais dans le code ni dans Git.
- L'application mobile ou le site **n'appellent jamais directement** l'API de l'opérateur : ils passent par ton serveur. Une clé embarquée dans une application mobile est une clé publique : n'importe qui peut la récupérer en décompilant l'APK.
- Une clé par environnement (test, production), et une rotation au moindre doute.

Vérifie l'historique Git de temps en temps : une clé commitée puis supprimée reste lisible dans l'historique. Des outils comme `gitleaks` le font automatiquement.

## 2. Le montant vient de ta base, jamais du client

L'erreur la plus fréquente, et la plus coûteuse : faire confiance au montant envoyé par le navigateur ou l'application.

```js
// À NE PAS FAIRE : le client choisit ce qu'il paie
app.post('/paiements', async (req, res) => {
  await operateur.demarrer({ montant: req.body.montant, commande: req.body.commandeId });
});

// Correct : le montant est relu côté serveur
app.post('/paiements', async (req, res) => {
  const commande = await Commande.trouverPourClient(req.body.commandeId, req.utilisateur.id);
  if (!commande || commande.statut !== 'en_attente') return res.sendStatus(409);

  await operateur.demarrer({ montant: commande.montantFcfa, reference: commande.reference });
});
```

Même chose à l'arrivée : quand l'opérateur confirme un paiement, **vérifie que le montant confirmé correspond** à celui de la commande avant de la valider.

## 3. Les webhooks : signés, vérifiés, idempotents

Un webhook est une URL publique. N'importe qui peut l'appeler.

- **Vérifie la signature** quand l'opérateur en fournit une (Wave signe ses webhooks avec un HMAC). Compare avec une fonction à temps constant (`crypto.timingSafeEqual`, `hash_equals`).
- **Vérifie l'origine** autrement quand il n'y a pas de signature : liste d'adresses IP de l'opérateur, jeton secret dans l'URL, ou mieux, **relis le statut** auprès de l'API de l'opérateur avant d'agir.
- **Rejette les messages trop vieux** (horodatage de plus de quelques minutes) pour bloquer le rejeu.
- **Traite chaque événement une seule fois**, grâce à une contrainte d'unicité sur son identifiant.

```js
import crypto from 'node:crypto';

function signatureValide(corpsBrut, entete, secret) {
  const [t, v1] = entete.split(',').map((p) => p.split('=')[1]);
  const attendu = crypto.createHmac('sha256', secret).update(t + corpsBrut).digest('hex');

  return (
    v1?.length === attendu.length &&
    crypto.timingSafeEqual(Buffer.from(v1), Buffer.from(attendu)) &&
    Math.abs(Date.now() / 1000 - Number(t)) < 300
  );
}
```

Avec Express, récupère le **corps brut** (`express.raw({ type: 'application/json' })` sur cette route) : un JSON décodé puis réencodé ne correspond plus à la signature.

## 4. Les états d'un paiement ne vont que dans un sens

Un paiement passe de `en_attente` à `reussi` ou `echoue`. Jamais de `reussi` à `en_attente`, jamais deux fois `reussi`. Encode ces transitions dans le code, et protège-les dans la base :

```sql
UPDATE paiements
SET statut = 'reussi', confirme_le = now()
WHERE id = $1 AND statut = 'en_attente';
```

Si la requête ne modifie aucune ligne, le paiement avait déjà été traité : on ne livre pas une deuxième fois.

## 5. Limiter, journaliser, alerter

- **Limite de débit** sur la création de paiements, par utilisateur et par adresse IP. Un attaquant qui teste des milliers de numéros de téléphone doit être bloqué vite.
- **Journalise** chaque appel à l'opérateur et chaque webhook reçu, avec la réponse brute. Le jour d'un litige, c'est ta seule preuve.
- **Mais ne journalise jamais** les codes PIN, les jetons d'accès complets, ni les numéros de carte.
- **Alerte** sur les anomalies : pic de paiements échoués, webhook à la signature invalide, montant inhabituel.

## 6. Les numéros de téléphone sont des données personnelles

Un numéro Mobile Money est lié à une identité. Affiche-le masqué (`+254 7•• ••• 412`), limite qui peut le voir dans ton équipe, et supprime ce dont tu n'as plus besoin. Les lois sur les données personnelles existent dans la plupart de nos pays, et elles sont de plus en plus appliquées.

## 7. Tester les cas méchants

Tes tests automatiques doivent couvrir les attaques, pas seulement le parcours heureux :

- un webhook avec une signature fausse est refusé ;
- le même webhook reçu deux fois ne livre qu'une fois ;
- un montant confirmé différent de la commande bloque la validation ;
- un utilisateur ne peut pas payer ni consulter la commande d'un autre.

## La liste, en bref

1. Secrets côté serveur uniquement, jamais dans l'application ni dans Git.
2. Montant relu en base, et revérifié à la confirmation.
3. Webhooks signés, vérifiés, horodatés, idempotents.
4. Transitions d'état à sens unique, protégées en base.
5. Limites de débit, journaux utiles, alertes.
6. Numéros masqués et données minimisées.
7. Des tests pour chaque attaque.

Rien de tout cela n'est propre au Mobile Money. Mais quand l'argent est en jeu, chaque oubli finit par coûter cher, et souvent à des clients qui ne peuvent pas se le permettre.
MD,
];
