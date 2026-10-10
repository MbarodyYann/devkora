<?php

return [
    'author' => 'Linda Ngo Bassa', 'category' => 'project_story', 'age' => 0.28, 'useful' => 11,
    'title' => 'Retour de projet : notre application de tontine, de l\'idée aux 800 membres',
    'tags' => ['React Native', 'MTN MoMo', 'Node.js'],
    'body' => <<<'MD'
Il y a dix-huit mois, ma tante m'a demandé si je pouvais « faire une application » pour sa tontine. Quinze femmes, une cotisation de 25 000 FCFA par mois, un cahier tenu par la trésorière, et régulièrement des disputes sur qui avait payé quoi. Aujourd'hui, l'application gère une soixantaine de tontines et environ 800 membres à Douala et Yaoundé. Voici notre parcours, nos erreurs, et ce que je referais autrement.

## Le vrai problème n'était pas technique

Ma première version était une belle application React Native : création de tontine, calendrier des tours, statistiques, notifications. Je l'ai montrée à ma tante. Elle m'a demandé : « Et comment je sais que Mireille a payé ? »

J'avais construit un outil de gestion. Elle avait besoin d'un outil de **confiance**. Ce qui comptait pour elle :

1. Voir **qui a payé** ce mois-ci, sans demander à la trésorière.
2. Que la trésorière **ne puisse pas modifier** les paiements passés.
3. Savoir **qui touche** au prochain tour, et quand.

J'ai jeté la moitié des écrans. La nouvelle version tenait en trois : la liste des membres avec une coche pour ceux qui ont payé, l'historique des tours, et un bouton « Cotiser ».

## Les choix techniques

- **React Native avec Expo**, parce que je connaissais React et qu'il fallait aller vite.
- **Une API Node.js avec PostgreSQL.** Les données financières méritent une vraie base relationnelle, avec des transactions.
- **MTN MoMo et Orange Money** pour les cotisations, via leurs API de collecte.
- **Des SMS** pour les rappels et les confirmations : toutes les membres n'ouvrent pas l'application, mais toutes lisent leurs SMS.

Le choix le plus important a été de rendre les paiements **immuables** : un paiement enregistré ne se modifie ni ne se supprime. Une erreur se corrige par une écriture de correction, visible de tous :

```sql
CREATE TABLE mouvements (
    id BIGSERIAL PRIMARY KEY,
    tontine_id BIGINT NOT NULL REFERENCES tontines(id),
    membre_id BIGINT NOT NULL REFERENCES membres(id),
    montant_fcfa INTEGER NOT NULL,          -- négatif pour une correction
    nature TEXT NOT NULL CHECK (nature IN ('cotisation', 'versement', 'correction')),
    reference_operateur TEXT UNIQUE,        -- identifiant MoMo ou Orange Money
    motif TEXT,
    cree_le TIMESTAMPTZ NOT NULL DEFAULT now()
);

REVOKE UPDATE, DELETE ON mouvements FROM app_user;
```

L'utilisateur de la base utilisé par l'application n'a tout simplement pas le droit de modifier ou de supprimer un mouvement. C'est ce détail, expliqué simplement, qui a convaincu les premières trésorières.

## Ce qui a marché

**Partir d'une seule tontine.** Pendant trois mois, l'application n'a servi qu'à celle de ma tante. J'étais à chaque réunion mensuelle. J'ai appris plus en trois réunions qu'en un mois de développement.

**Le SMS de confirmation.** Après chaque cotisation, tout le groupe reçoit : « Mireille a cotisé 25 000 FCFA pour le tour de mars. 12/15 ont payé. » C'est devenu la fonction la plus appréciée, alors qu'elle ne demandait aucun écran.

**Le bouche-à-oreille.** Nous n'avons jamais fait de publicité. Chaque membre fait partie d'autres tontines, et elle en parle.

## Ce qui n'a pas marché

**Le paiement automatique.** J'avais prévu de prélever automatiquement les cotisations. Personne n'en voulait : les membres veulent choisir le moment où elles paient, souvent après la paie ou le marché. Nous avons remplacé le prélèvement par des rappels.

**Les tontines « avec enchères ».** Certaines tontines attribuent le tour à celle qui propose le plus gros intérêt. J'ai passé six semaines sur cette fonction. Trois tontines l'utilisent. J'aurais dû demander avant de construire.

**Sous-estimer les frais.** Les frais de transaction Mobile Money, sur des milliers de petites cotisations, représentaient une somme. Il a fallu les expliquer clairement et laisser chaque tontine décider qui les paie.

## Les chiffres, honnêtement

- Environ 60 tontines actives et 800 membres.
- Un taux de retard de cotisation passé, selon les trésorières, de « presque tous les mois » à quelques cas par trimestre.
- Un modèle économique encore fragile : un petit abonnement payé par tontine, qui couvre les serveurs et les SMS, pas encore mon temps.

## Ce que je referais autrement

1. **Observer avant de coder.** Assister à trois réunions avant d'écrire la première ligne.
2. **Construire la confiance d'abord**, les fonctions ensuite. Dans un projet financier, la question n'est pas « que peut-on faire ? » mais « pourquoi me croirait-on ? ».
3. **Demander avant de construire** chaque fonction coûteuse. Une question de cinq minutes peut économiser six semaines.
4. **Prévoir les frais** dès le départ, et les afficher clairement.

## Et maintenant

Nous travaillons à une version USSD pour les membres sans smartphone, et à l'ouverture vers la Côte d'Ivoire. Je cherche un dev qui connaît bien l'API Orange Money ivoirienne pour en discuter. Et si toi aussi tu construis quelque chose autour de l'épargne collective, écrivons-nous : il y a beaucoup à apprendre les uns des autres.
MD,
];
