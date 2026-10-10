<?php

return [
    'author' => 'Koffi Mensah', 'category' => 'experience', 'age' => 0.44, 'useful' => 12,
    'title' => 'Notre hackathon à Lomé : 48 heures, 3 pays, une démo qui a failli tomber',
    'tags' => ['Hackathon', 'Django', 'React'],
    'body' => <<<'MD'
Le mois dernier, avec une développeuse de Porto-Novo et un designer d'Accra rencontrés en ligne, nous avons participé à un hackathon de 48 heures à Lomé sur le thème de l'inclusion financière. Nous n'avons pas gagné. Nous avons fini deuxièmes, avec une démo qui a failli s'effondrer sur scène. Voici ce que nous avons appris, en espérant que ça serve aux prochaines équipes.

## Former une équipe à distance, avant le jour J

Nous ne nous connaissions pas. Nadège avait posté dans un groupe qu'elle cherchait un dev back-end. Kofi, le designer, nous a rejoints une semaine avant.

Ce qui a marché :

- **Un appel vidéo d'une heure**, une semaine avant, pour se présenter, parler de ce que chacun sait faire, et surtout de ce qu'il ne sait pas faire.
- **Un dépôt Git créé à l'avance**, avec un squelette d'application qui démarrait. Le règlement l'autorisait ; renseignez-vous, ce n'est pas toujours le cas.
- **Des rôles clairs** : back-end et API pour moi, interface web pour Nadège, parcours et maquettes pour Kofi.

Ce qui a moins bien marché : nous parlions tous français, mais Kofi est plus à l'aise en anglais. Nous avons perdu du temps sur des malentendus. Écrire les décisions importantes, même en deux lignes, aurait évité ça.

## Le choix du sujet : un problème réel, petit

Le thème était large. Plutôt que de viser « la banque de demain », nous avons choisi un problème que deux d'entre nous connaissaient de près : **la gestion des tontines**. Dans beaucoup de familles et de quartiers, l'argent est collecté en liquide, noté dans un cahier, et les disputes sur « qui a payé quoi » sont fréquentes.

Notre idée : une tontine où chaque cotisation passe par Mobile Money et où chacun voit, en temps réel, qui a payé et qui touche au prochain tour.

Le jury nous a dit ensuite que c'était notre force : un problème que tout le monde dans la salle comprenait en dix secondes.

## Les 48 heures

**Vendredi soir.** Trois heures sur papier avant d'écrire une ligne de code. Nous avons dessiné les trois écrans indispensables, et rayé tout le reste. Notre liste « plus tard » faisait une page entière.

**Samedi.** Le back-end Django avec trois modèles (tontine, membre, cotisation), l'interface React, et un faux opérateur de paiement qui simulait Mobile Money. Nous avons décidé tôt de **ne pas brancher une vraie API de paiement** : les sandbox peuvent être lentes, et nous ne voulions pas que notre démo en dépende.

**Dimanche matin.** Kofi a refait entièrement la page principale après avoir observé trois participants l'utiliser. Personne ne comprenait le mot « cycle ». Nous l'avons remplacé par « tour ». Un mot changé, une interface comprise.

## La démo qui a failli tomber

Quinze minutes avant notre passage, le Wi-Fi de la salle a lâché. Notre application tournait sur un serveur en ligne. Panique.

Ce qui nous a sauvés :

1. Nadège avait **la même application qui tournait en local** sur son ordinateur, avec les mêmes données de démo.
2. Nous avions **une vidéo de la démo**, enregistrée la veille au soir, au cas où.
3. Mon téléphone avait un **forfait de données chargé**, et nous avons pu partager la connexion pour la partie qui en avait besoin.

Nous avons fait la démo en local. Personne dans le jury ne s'en est rendu compte.

## Ce que le jury a apprécié

- **Le problème réel**, expliqué en une phrase.
- **Une démo qui raconte une histoire** : « Voici Afi, elle participe à une tontine de dix personnes. Elle cotise depuis son téléphone, et tout le groupe le voit. » Pas une visite des menus.
- **Les choix assumés** : « Le paiement est simulé, voici comment nous brancherions MTN MoMo et Flooz. »

Ce qu'il nous a reproché : **aucune réflexion sur la confiance**. Qui garde l'argent entre deux tours ? Que se passe-t-il si quelqu'un ne paie pas ? Nous avions le code, pas les réponses.

## Mes conseils pour votre prochain hackathon

1. **Choisissez un petit problème que vous connaissez.** Mieux vaut une solution complète à un vrai problème qu'une ébauche de grande vision.
2. **Trois écrans, pas dix.** Faites une liste « plus tard » et tenez-vous-y.
3. **Ne dépendez pas du réseau pour la démo.** Données locales, vidéo de secours, partage de connexion testé.
4. **Testez sur de vraies personnes, même cinq minutes.** Un mot mal choisi peut tout bloquer.
5. **Racontez l'histoire d'une personne**, pas les fonctionnalités.
6. **Dormez un peu.** Le dimanche matin, après quatre heures de sommeil, nous avons corrigé en vingt minutes un bug qui nous avait résisté deux heures dans la nuit.

## Et après ?

Nous avons continué le projet ensemble, à distance, quelques heures par semaine. Nous cherchons encore un dev qui connaît bien l'intégration de Flooz et de T-Money au Togo. Si c'est toi, écris-moi : c'est exactement pour ce genre de rencontre que les communautés de devs existent.
MD,
];
