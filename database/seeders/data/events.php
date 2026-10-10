<?php

/*
| Événements de démonstration (voir EventSeeder).
| in : début dans N jours (positif = à venir, toujours dans le futur quelle que soit la date du semis ;
| négatif = passé). hours : durée. published : publié il y a N jours (dans la fenêtre de DemoClock).
| participants : membres qui ont dit « J'y participe ».
*/

return [
    [
        'author' => 'Aïssatou Koné', 'type' => 'hackathon', 'in' => 20, 'hours' => 48, 'at' => '09:00', 'published' => 8,
        'city' => 'Abidjan', 'country' => 'CI', 'online' => false, 'url' => 'https://exemple.ci/hackathon-inclusion-financiere',
        'title' => 'Hackathon Inclusion financière d\'Abidjan',
        'description' => <<<'MD'
Deux jours pour construire des services financiers utiles : tontines, épargne de groupe, paiements pour les petits commerçants, assurance santé solidaire.

- Équipes de 2 à 5 personnes, de tous les pays.
- Sandbox Mobile Money disponibles sur place.
- Mentors en back-end, mobile et design.

Tu n'as pas encore d'équipe ? Publie une annonce depuis cette page ou rejoins une équipe qui se forme.
MD,
        'participants' => ['Adjoua N\'Guessan', 'Kwame Asante', 'Yao Kouassi', 'Linda Ngo Bassa', 'Chinedu Okafor', 'Ama Owusu'],
    ],
    [
        'author' => 'Moussa Diop', 'type' => 'meetup', 'in' => 6, 'hours' => 3, 'at' => '18:30', 'published' => 5,
        'city' => 'Dakar', 'country' => 'SN', 'online' => false, 'url' => null,
        'title' => 'Meetup Laravel Dakar : déployer sur un VPS local',
        'description' => <<<'MD'
Deuxième meetup Laravel de Dakar. Au programme : déployer une application Laravel sur un VPS hébergé au Sénégal (Nginx, PHP-FPM, PostgreSQL, sauvegardes), puis questions ouvertes.

Apporte ton ordinateur si tu veux essayer pendant la séance. Accès libre, places limitées à 40.
MD,
        'participants' => ['Fatou Ndiaye', 'Awa Sarr', 'Ibrahima Sow', 'Cheikh Fall', 'Abdoul Kaboré'],
    ],
    [
        'author' => 'Aïssatou Koné', 'type' => 'workshop', 'in' => 3, 'hours' => 2, 'at' => '19:00', 'published' => 4,
        'city' => null, 'country' => null, 'online' => true, 'url' => null,
        'title' => 'Atelier en ligne : Flutter hors ligne d\'abord',
        'description' => <<<'MD'
Un atelier pratique de deux heures, en ligne, à partir de mon article : base locale, file d'envoi, synchronisation par lots et conflits.

Pour les devs Flutter qui ont déjà fait une première application. Le lien de la visioconférence est envoyé aux participants la veille. La séance est pensée pour les connexions modestes : caméra coupée, code partagé à l'avance.
MD,
        'participants' => ['Rasmata Ouédraogo', 'Cheikh Fall', 'Ama Owusu', 'Grâce Mukendi', 'Linda Ngo Bassa', 'Kadiatou Diallo', 'Aisha Bello'],
    ],
    [
        'author' => 'Koffi Mensah', 'type' => 'meetup', 'in' => 10, 'hours' => 3, 'at' => '17:00', 'published' => 3,
        'city' => 'Lomé', 'country' => 'TG', 'online' => false, 'url' => null,
        'title' => 'Meetup des devs de Lomé : paiements mobiles et USSD',
        'description' => <<<'MD'
Rencontre des devs de Lomé autour des paiements mobiles : retours d'expérience sur Flooz et T-Money, et une démonstration d'un menu USSD de bout en bout.

Ouvert à tous les niveaux. On finit par un moment d'échange pour former des binômes de projets.
MD,
        'participants' => ['Sitsofe Amouzou', 'Edem Agbeko', 'Nadège Dossou'],
    ],
    [
        'author' => 'Junior Tchoupo', 'type' => 'workshop', 'in' => 14, 'hours' => 3, 'at' => '15:00', 'published' => 2,
        'city' => 'Yaoundé', 'country' => 'CM', 'online' => false, 'url' => 'https://exemple.cm/atelier-securite',
        'title' => 'Atelier sécurité des API Mobile Money',
        'description' => <<<'MD'
Atelier pratique : vérifier une signature de webhook, rendre un traitement idempotent, ne jamais faire confiance au montant du client. Exercices en Node.js et en PHP.

Inscription chez l'organisateur, places limitées.
MD,
        'participants' => ['Christian Mbarga', 'Youssef El Amrani'],
    ],
    // Passés.
    [
        'author' => 'Rodrigue Hounkpatin', 'type' => 'meetup', 'in' => -6, 'hours' => 3, 'at' => '18:00', 'published' => 10,
        'city' => 'Cotonou', 'country' => 'BJ', 'online' => false, 'url' => null,
        'title' => 'Meetup Laravel Cotonou : les bonnes pratiques en 3G',
        'description' => <<<'MD'
Retour sur les pratiques de notre agence pour des applications rapides en 3G : images, JavaScript, cache et pagination. Les supports de la présentation sont dans mon article sur le sujet.
MD,
        'participants' => ['Nadège Dossou', 'Sitsofe Amouzou', 'Edem Agbeko', 'Yao Kouassi'],
    ],
    [
        'author' => 'Koffi Mensah', 'type' => 'hackathon', 'in' => -9, 'hours' => 48, 'at' => '09:00', 'published' => 11,
        'city' => 'Lomé', 'country' => 'TG', 'online' => false, 'url' => null,
        'title' => 'Hackathon fintech de Lomé',
        'description' => <<<'MD'
48 heures autour de l'inclusion financière, avec des équipes du Togo, du Bénin et du Ghana. Retrouve le récit de notre équipe dans l'article « Notre hackathon à Lomé ».
MD,
        'participants' => ['Nadège Dossou', 'Kwame Asante', 'Sitsofe Amouzou'],
    ],
    [
        'author' => 'Jean-Paul Habimana', 'type' => 'workshop', 'in' => -2, 'hours' => 2, 'at' => '18:00', 'published' => 7,
        'city' => null, 'country' => null, 'online' => true, 'url' => null,
        'title' => 'Atelier en ligne : contribuer à un projet open source',
        'description' => <<<'MD'
Une première contribution open source pas à pas : choisir un ticket, créer sa branche, écrire un test, ouvrir une demande de fusion et répondre à la relecture.
MD,
        'participants' => ['Aline Uwase', 'Mamadou Bah', 'Ousmane Issoufou', 'Chinedu Okafor', 'Junior Tchoupo'],
    ],
];
