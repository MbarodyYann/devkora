<?php

return [
    'author' => 'Yao Kouassi', 'category' => 'best_practice', 'age' => 0.65, 'useful' => 13,
    'title' => 'Fixer ses tarifs de freelance en FCFA sans se brader',
    'tags' => ['Freelance', 'Laravel'],
    'body' => <<<'MD'
Quand j'ai commencé en freelance à Bouaké, je fixais mes prix au feeling. Résultat : des projets à perte, des clients qui en demandaient toujours plus, et l'impression de travailler beaucoup pour peu. Trois ans plus tard, voici la méthode que j'applique, et que je partage avec les devs qui débutent.

## 1. Partir de ce dont tu as besoin, pas de ce que tu oses demander

Commence par un calcul honnête de ce que te coûte un mois de vie et de travail :

- tes dépenses personnelles (logement, nourriture, transport, famille) ;
- tes frais professionnels (internet, électricité, ordinateur à renouveler, logiciels, déplacements) ;
- une épargne de précaution, parce que les mois sans client existent ;
- les cotisations et impôts de ton pays.

Imaginons un total de **600 000 FCFA par mois**.

## 2. Compter les jours vraiment facturables

Un mois compte une vingtaine de jours ouvrés, mais tu ne factures pas tous ces jours. Il y a la prospection, les devis, l'administratif, la formation, les jours sans mission. En pratique, la plupart des freelances facturent **10 à 14 jours par mois**.

Avec 12 jours facturables :

```
600 000 FCFA ÷ 12 jours = 50 000 FCFA par jour, au minimum
```

Ce chiffre est ton **plancher**. En dessous, tu perds de l'argent, même si tu as l'impression de travailler.

## 3. Ajuster selon le marché et ta valeur

Le plancher dit ce qu'il te faut, pas ce que tu vaux. Pour te situer :

- **Regarde les tarifs de devs de ton niveau**, dans ta ville et pour le même type de client. Une PME locale, une ONG et une entreprise étrangère n'ont pas le même budget.
- **Valorise ce qui est rare.** Savoir intégrer Wave, Orange Money et MTN MoMo, ou construire une application qui marche hors ligne, ce sont des compétences que peu de devs maîtrisent. Elles justifient un tarif plus élevé.
- **Monte tes prix régulièrement.** À chaque nouveau client, essaie 10 % de plus. Si personne ne refuse jamais, tu es trop bas.

## 4. Chiffrer un projet : au temps, pas au feeling

Pour un devis, découpe le projet en tâches de une demi-journée à deux jours, estime chacune, puis ajoute une marge pour l'imprévu.

Exemple pour l'ajout du paiement Wave sur un site existant :

| Tâche | Estimation |
|---|---|
| Lecture du code, accès, configuration | 0,5 jour |
| Session de paiement et redirection | 1 jour |
| Webhook signé et confirmation de commande | 1 jour |
| Tests réels et corrections | 0,5 jour |
| Documentation et passation | 0,5 jour |
| **Total** | **3,5 jours** |
| Marge d'imprévu (20 %) | 0,7 jour |

Avec un tarif de 60 000 FCFA par jour, on arrive à environ **250 000 FCFA**. Le client voit le détail, comprend le prix, et discute des tâches plutôt que de ton tarif.

## 5. Protéger ton travail par écrit

Un devis accepté par écrit (même un message WhatsApp clair vaut mieux que rien) doit préciser :

- **Ce qui est inclus**, et surtout **ce qui ne l'est pas** : « Les remboursements et le tableau de bord des transactions ne sont pas inclus. »
- **Le nombre d'allers-retours** de corrections compris dans le prix.
- **L'acompte** : 30 à 50 % avant de commencer. Un client qui refuse tout acompte est un signal d'alarme.
- **Le calendrier de paiement** : par exemple 40 % au départ, 40 % à la livraison de la version de test, 20 % à la mise en production.
- **Le moyen de paiement** : Wave, Orange Money, virement. Précise qui paie les frais.

## 6. Les erreurs que j'ai faites

- **Accepter « juste une petite modification »** dix fois de suite. Chaque petite modification se note, et au-delà de ce qui était prévu, elle se facture.
- **Travailler sans acompte** pour un nouveau client. Une fois, le projet a été annulé à 80 % et je n'ai jamais été payé.
- **Baisser mon prix dès la première objection.** Aujourd'hui, je réduis le périmètre plutôt que le tarif : « Pour ce budget, je peux livrer le paiement Wave sans le tableau de bord. »

## 7. La maintenance, ce revenu oublié

Une application en production a besoin de mises à jour de sécurité, de certificats renouvelés, de sauvegardes vérifiées, et les API de paiement évoluent. Propose un **forfait mensuel de maintenance**, avec un nombre d'heures inclus. Pour toi, c'est un revenu régulier. Pour le client, c'est la tranquillité.

## En résumé

1. Calcule ton plancher : besoins mensuels divisés par tes jours facturables.
2. Situe-toi sur le marché, et valorise tes compétences rares.
3. Chiffre chaque projet tâche par tâche, avec une marge.
4. Écris ce qui est inclus et ce qui ne l'est pas, et demande un acompte.
5. Réduis le périmètre plutôt que ton prix.
6. Propose de la maintenance.

Les devs qui se bradent ne rendent service ni à eux-mêmes, ni aux autres : ils tirent tout le marché vers le bas. Fixer un prix juste, c'est aussi une forme de solidarité.
MD,
];
