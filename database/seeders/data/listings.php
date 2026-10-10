<?php

/*
| Annonces de démonstration (voir ListingSeeder).
| age : position dans la fenêtre de DemoClock (1 = début, 0 = maintenant) ; country null = télétravail.
| Équipes : members (acceptés), pending (en attente), closed (équipe complète).
| Missions : status open | in_progress | completed | cancelled ; retained = dev retenu (fonds sécurisés) ;
| review = avis de l'auteur [note, communication, qualité, délai, commentaire].
*/

return [
    'teams' => [
        [
            'author' => 'Aïssatou Koné', 'age' => 0.35, 'country' => 'CI', 'hours' => 10,
            'title' => 'Hackathon fintech d\'Abidjan : il nous manque un dev back-end',
            'roles' => 'Dev back-end Laravel ou Node.js',
            'skills' => ['Flutter', 'Laravel', 'MTN MoMo'],
            'description' => <<<'MD'
Le hackathon « Inclusion financière » d'Abidjan a lieu dans trois semaines. Nous sommes deux : moi (Flutter) et Adjoua (design). Notre idée : une application de tontine où chaque cotisation passe par Mobile Money et où tout le groupe voit qui a payé.

Il nous manque un dev back-end pour l'API (Laravel ou Node.js) et l'intégration MTN MoMo en sandbox. L'événement se fait sur place, mais la préparation se fait à distance, deux soirs par semaine.
MD,
            'members' => [['Adjoua N\'Guessan', 'Je fais le design, on en a parlé la semaine dernière : je suis partante !']],
            'pending' => [['Kwame Asante', 'Je connais bien l\'API MTN MoMo (je l\'utilise à Accra) et Laravel. Je peux venir à Abidjan pour le week-end du hackathon.']],
        ],
        [
            'author' => 'Jean-Paul Habimana', 'age' => 0.9, 'country' => null, 'hours' => 4,
            'title' => 'Bibliothèque open source de validation des numéros de téléphone africains',
            'roles' => 'Contributeurs TypeScript, testeurs par pays',
            'skills' => ['TypeScript', 'Open source', 'SMS'],
            'description' => <<<'MD'
Je maintiens une petite bibliothèque TypeScript qui valide et normalise les numéros de téléphone des pays africains, avec les préfixes des opérateurs (utile pour choisir le bon moyen de paiement).

Je cherche des contributeurs qui connaissent les plans de numérotation de leur pays et peuvent écrire des tests. Quelques heures par mois suffisent, tout se passe sur GitHub.
MD,
            'members' => [
                ['Chinedu Okafor', 'Je peux m\'occuper du Nigeria et du Ghana, les préfixes changent souvent.'],
                ['Ousmane Issoufou', 'Partant pour le Niger, et je connais bien les numéros burkinabè aussi.'],
                ['Mamadou Bah', 'Je prends la Guinée et je relis la partie Côte d\'Ivoire.'],
            ],
        ],
        [
            'author' => 'Amine Trabelsi', 'age' => 0.25, 'country' => null, 'hours' => 5,
            'title' => 'Corpus vocal en wolof et en bambara pour la reconnaissance de la parole',
            'roles' => 'Locuteurs wolof et bambara, dev Python',
            'skills' => ['Python', 'Machine learning', 'Data science'],
            'description' => <<<'MD'
Suite à la discussion dans les questions : je lance la constitution d'un petit corpus vocal ouvert en wolof et en bambara (chiffres, montants, noms de villes), utile pour des services vocaux et USSD.

Il faut des locuteurs pour relire les transcriptions et un dev Python pour l'outil de collecte. Le corpus sera publié sous licence ouverte.
MD,
            'members' => [
                ['Mariam Traoré', 'Je m\'occupe du bambara, et je connais des étudiants à Bamako qui peuvent enregistrer.'],
                ['Fatou Ndiaye', 'Je relis le wolof, avec l\'orthographe du CLAD.'],
            ],
            'pending' => [['Edem Agbeko', 'Je peux construire l\'outil de collecte en Python (petite appli web légère).']],
        ],
        [
            'author' => 'Rasmata Ouédraogo', 'age' => 0.6, 'country' => 'BF', 'hours' => 8,
            'title' => 'Application de suivi des récoltes pour une coopérative de Bobo-Dioulasso',
            'roles' => 'Dev back-end, testeur terrain',
            'skills' => ['Kotlin', 'Hors ligne', 'Django'],
            'description' => <<<'MD'
J'ai une première version Android (Kotlin) qui enregistre les récoltes hors ligne pour une coopérative de producteurs de sésame. Il manque le serveur de synchronisation et un tableau de bord pour les responsables.

Projet bénévole pour l'instant, avec une petite subvention possible l'an prochain.
MD,
            'members' => [['Aisha Bello', 'Je travaille sur des services agricoles à Abuja, je peux faire le back-end Django.']],
            'closed' => true,
        ],
        [
            'author' => 'Patrick Kabongo', 'age' => 0.75, 'country' => 'CD', 'hours' => 10,
            'title' => 'Startup edtech à Kinshasa : cofondateur technique recherché',
            'roles' => 'Cofondateur ou cofondatrice technique (full-stack)',
            'skills' => ['Node.js', 'React', 'PostgreSQL'],
            'description' => <<<'MD'
Nous construisons un outil de gestion des notes et des paiements de frais scolaires pour les écoles privées de Kinshasa. Trois écoles l'utilisent déjà en test.

Je cherche un ou une cofondatrice technique, prête à s'engager sur la durée, avec des parts dans la société. Expérience Node.js et React appréciée.
MD,
            'pending' => [['Grâce Mukendi', 'Je suis à Lubumbashi, développeuse .NET, et je veux passer sur un projet qui a du sens. On peut en parler ?']],
        ],
        [
            'author' => 'Koffi Mensah', 'age' => 0.4, 'country' => 'TG', 'hours' => 6,
            'title' => 'Suite du hackathon de Lomé : intégrer Flooz et T-Money à notre tontine',
            'roles' => 'Dev qui connaît Flooz ou T-Money',
            'skills' => ['Django', 'React', 'Moov Money'],
            'description' => <<<'MD'
Après le hackathon de Lomé (voir mon article), nous continuons le projet de tontine à trois. Il nous manque quelqu'un qui connaît l'intégration de Flooz (Moov Africa) ou de T-Money au Togo.

Quelques heures par semaine, à distance. Le projet est encore bénévole.
MD,
            'members' => [['Nadège Dossou', 'Je continue sur l\'interface, comme pendant le hackathon.']],
            'pending' => [['Sitsofe Amouzou', 'Je suis à Lomé et j\'ai intégré T-Money pour une école l\'an dernier.']],
        ],
        [
            'author' => 'Junior Tchoupo', 'age' => 0.15, 'country' => 'CM', 'hours' => 6,
            'title' => 'Équipe pour le prochain CTF de cybersécurité en ligne',
            'roles' => 'Profils web, réseau et cryptographie',
            'skills' => ['Cybersécurité', 'Linux', 'Python'],
            'description' => <<<'MD'
Un CTF en ligne ouvert aux équipes africaines a lieu dans un mois. Je cherche trois ou quatre personnes pour former une équipe : idéalement un profil web, un profil réseau et quelqu'un qui aime la cryptographie.

On s'entraîne ensemble deux soirs par semaine sur d'anciens défis.
MD,
            'pending' => [['Youssef El Amrani', 'J\'ai fait plusieurs CTF à Casablanca, plutôt profil réseau. Partant !']],
        ],
        [
            'author' => 'Awa Sarr', 'age' => 0.55, 'country' => 'SN', 'hours' => 5,
            'title' => 'Kit d\'interface open source pour les parcours de paiement mobile',
            'roles' => 'Designer, dev React, dev Flutter',
            'skills' => ['UI/UX', 'React', 'Flutter'],
            'description' => <<<'MD'
Je veux publier un kit d'interface libre pour les parcours de paiement Mobile Money : choix de l'opérateur, saisie du numéro, attente de validation, succès et échec, avec des textes en français testés auprès d'utilisateurs.

Composants React et Flutter, maquettes Figma. Quelques heures par semaine.
MD,
            'members' => [
                ['Adjoua N\'Guessan', 'Je fais les maquettes Figma avec toi.'],
                ['Aïssatou Koné', 'Je prends la version Flutter.'],
                ['Fatou Ndiaye', 'Je prends la version React.'],
            ],
            'closed' => true,
        ],
        [
            'author' => 'Cheikh Fall', 'age' => 0.06, 'country' => 'SN', 'hours' => 4,
            'title' => 'Appli pour les pêcheurs de Casamance : cherche un mentor Flutter',
            'roles' => 'Dev Flutter expérimenté (mentorat)',
            'skills' => ['Flutter', 'Hors ligne', '3G'],
            'description' => <<<'MD'
Je débute en Flutter et je construis une application pour que les pêcheurs de Ziguinchor enregistrent leurs prises et les prix du jour, souvent sans réseau.

Je cherche un dev expérimenté qui accepterait de relire mon code une heure par semaine et de m'aider sur la partie hors ligne.
MD,
        ],
        [
            'author' => 'Hery Rakotomalala', 'age' => 0.3, 'country' => 'MG', 'hours' => 6,
            'title' => 'Module Odoo de paiement Mobile Money pour les PME de Madagascar',
            'roles' => 'Dev Python ou Odoo',
            'skills' => ['Odoo', 'Python', 'API'],
            'description' => <<<'MD'
Les PME malgaches que j'accompagne veulent encaisser par Mobile Money directement depuis Odoo. Je lance un module libre et je cherche un ou deux devs Python pour le construire avec moi.
MD,
            'members' => [['Otieno Odhiambo', 'J\'ai intégré M-Pesa dans plusieurs outils, je peux aider sur la partie paiement.']],
        ],
    ],

    'missions' => [
        // Aïssatou : mission ouverte avec 3 propositions, dont Moussa.
        [
            'author' => 'Aïssatou Koné', 'age' => 0.12, 'status' => 'open', 'country' => null, 'budget' => 180000, 'days' => 10,
            'title' => 'Je suis débordée : module Orange Money pour une application de caisse',
            'skills' => ['Laravel', 'Orange Money', 'API'],
            'description' => <<<'MD'
Je livre une application de caisse (Flutter, avec une API Laravel) à une chaîne de boutiques d'Abidjan, et je suis débordée. Je cherche quelqu'un pour le module de paiement Orange Money côté API : création du paiement, notification, vérification du statut, remboursement.

Le reste de l'API existe et est testé. Je fournis l'accès au dépôt et aux identifiants sandbox. Livraison avec tests automatisés.
MD,
            'proposals' => [
                ['Moussa Diop', 165000, 8, 'J\'ai intégré Orange Money et Wave dans plusieurs API Laravel (voir mon article sur Wave). Je livre avec des tests sur la notification et l\'idempotence, et une courte documentation.'],
                ['Mamadou Bah', 150000, 10, 'J\'intègre Orange Money pour des sites marchands à Conakry depuis deux ans. Je connais les pièges de la sandbox (devise OUV, order_id unique).'],
                ['Yao Kouassi', 190000, 7, 'Je suis à Bouaké, je peux venir sur place à Abidjan si besoin. Laravel au quotidien, Orange Money ivoirien déjà intégré une fois.'],
            ],
        ],
        [
            'author' => 'Rodrigue Hounkpatin', 'age' => 0.95, 'status' => 'completed', 'country' => 'BJ', 'budget' => 250000, 'days' => 12,
            'title' => 'Sous-traitance : intégration MTN MoMo pour une plateforme de billetterie',
            'skills' => ['Laravel', 'MTN MoMo', 'PHP'],
            'description' => <<<'MD'
Notre agence livre une billetterie en ligne à Cotonou. Je confie l'intégration MTN MoMo (collecte, callback, vérification du statut) à un dev de confiance : l'équipe est prise sur le reste du projet.
MD,
            'proposals' => [
                ['Moussa Diop', 240000, 10, 'J\'ai fait la même intégration pour un client à Dakar. Je peux commencer lundi.'],
                ['Kwame Asante', 230000, 12, 'MTN MoMo est mon quotidien à Accra.'],
            ],
            'retained' => 'Moussa Diop', 'provider' => 'mtn_momo', 'phone' => '+22997123456',
            'review' => [5, 5, 5, 5, 'Travail impeccable : intégration propre, tests complets, et une documentation que toute l\'équipe a comprise. Livré deux jours avant le délai.'],
        ],
        [
            'author' => 'Linda Ngo Bassa', 'age' => 0.7, 'status' => 'completed', 'country' => null, 'budget' => 120000, 'days' => 7,
            'title' => 'Webhook Wave et confirmation par SMS pour notre application de tontine',
            'skills' => ['Wave', 'Node.js', 'SMS'],
            'description' => <<<'MD'
Nous ouvrons notre application de tontine au Sénégal. J'ai besoin du webhook Wave (signature, idempotence) et de l'envoi d'un SMS de confirmation à tout le groupe après chaque cotisation.
MD,
            'proposals' => [
                ['Moussa Diop', 115000, 6, 'Le webhook Wave signé, je l\'ai fait plusieurs fois. Je m\'adapte à Node.js sans problème.'],
                ['Mariam Traoré', 120000, 7, 'Node.js et SMS au quotidien à Bamako.'],
            ],
            'retained' => 'Mariam Traoré', 'provider' => 'wave', 'phone' => '+237699123456',
            'review' => [4, 5, 4, 4, 'Bon travail et très réactive. Un petit retard sur les tests, rattrapé rapidement.'],
        ],
        [
            'author' => 'Yao Kouassi', 'age' => 0.6, 'status' => 'completed', 'country' => 'CI', 'budget' => 90000, 'days' => 5,
            'title' => 'Tableau de bord Vue.js : finaliser trois écrans de statistiques',
            'skills' => ['Vue.js', 'Laravel', 'Tailwind CSS'],
            'description' => <<<'MD'
Trois écrans de statistiques à finaliser pour un client (ventes, stocks, clients), à partir de maquettes validées. L'API Laravel est prête.
MD,
            'proposals' => [
                ['Sitsofe Amouzou', 85000, 5, 'Je fais du Laravel et du Tailwind tous les jours, Vue.js aussi.'],
                ['Nadège Dossou', 70000, 7, 'Je débute mais je connais bien Tailwind, et ce serait une super expérience.'],
            ],
            'retained' => 'Sitsofe Amouzou', 'provider' => 'orange_money', 'phone' => '+2250707070707',
            'review' => [5, 5, 5, 4, 'Écrans conformes aux maquettes, code lisible. Je referai appel à elle.'],
        ],
        [
            'author' => 'Christian Mbarga', 'age' => 0.5, 'status' => 'completed', 'country' => 'CM', 'budget' => 300000, 'days' => 14,
            'title' => 'Menu USSD de consultation de solde pour une microfinance',
            'skills' => ['USSD', 'Java', 'Spring Boot'],
            'description' => <<<'MD'
Je conçois l'architecture, je cherche un dev pour développer le menu USSD (consultation de solde avec code PIN, blocage après trois erreurs, journalisation) en Spring Boot.
MD,
            'proposals' => [
                ['Boubacar Keïta', 280000, 14, 'Je travaille dans une microfinance à Bamako, l\'USSD pour les clients sans smartphone, c\'est exactement mon sujet.'],
            ],
            'retained' => 'Boubacar Keïta', 'provider' => 'mtn_momo', 'phone' => '+237677889900',
            'review' => [5, 4, 5, 5, 'Très bonne compréhension des contraintes de sécurité. Le code PIN et la journalisation sont exemplaires.'],
        ],
        [
            'author' => 'Abdoul Kaboré', 'age' => 0.45, 'status' => 'completed', 'country' => null, 'budget' => 75000, 'days' => 3,
            'title' => 'Script de sauvegarde PostgreSQL et restauration testée',
            'skills' => ['PostgreSQL', 'Linux', 'DevOps'],
            'description' => <<<'MD'
Je n'ai pas le temps cette semaine : il faut un script de sauvegarde quotidienne pour trois bases PostgreSQL, avec copie hors du serveur, alerte en cas d'échec et une procédure de restauration testée.
MD,
            'proposals' => [
                ['Otieno Odhiambo', 70000, 3, 'Je fais ça pour notre plateforme de micro-crédit, avec pgBackRest ou un simple pg_dump selon la taille.'],
                ['Léa Moussavou', 75000, 4, 'Docker et PostgreSQL au quotidien, je documente la restauration pas à pas.'],
            ],
            'retained' => 'Otieno Odhiambo', 'provider' => 'wave', 'phone' => '+22670112233',
            'review' => [3, 3, 4, 3, 'Le script fonctionne, mais la documentation était trop courte et il a fallu plusieurs échanges.'],
        ],
        [
            'author' => 'Moussa Diop', 'age' => 0.28, 'status' => 'completed', 'country' => 'SN', 'budget' => 60000, 'days' => 4,
            'title' => 'Intégrer les maquettes de la page de paiement d\'un site marchand',
            'skills' => ['Tailwind CSS', 'UI/UX', 'Wave'],
            'description' => <<<'MD'
Je m'occupe du back-end d'un site marchand à Dakar. Je cherche quelqu'un pour intégrer les maquettes de la page de paiement (choix de l'opérateur, attente, succès, échec) en Blade et Tailwind.
MD,
            'proposals' => [
                ['Awa Sarr', 60000, 3, 'Les parcours de paiement mobile, c\'est ma spécialité. Je peux aussi proposer des améliorations de textes.'],
            ],
            'retained' => 'Awa Sarr', 'provider' => 'wave', 'phone' => '+221771234567',
            'review' => [5, 5, 5, 5, 'Au-delà de l\'intégration, Awa a amélioré les textes : moins d\'abandons dès la première semaine.'],
        ],
        // En cours, fonds sécurisés.
        [
            'author' => 'Mariam Traoré', 'age' => 0.22, 'status' => 'in_progress', 'country' => 'ML', 'budget' => 140000, 'days' => 10,
            'title' => 'Application de livraison : écran de suivi de commande en React Native',
            'skills' => ['React Native', 'TypeScript', 'Firebase'],
            'description' => <<<'MD'
Je fais l'API de notre startup de livraison à Bamako. Je confie l'écran de suivi de commande (carte, étapes, notification) en React Native.
MD,
            'proposals' => [
                ['Linda Ngo Bassa', 135000, 9, 'Je fais une appli de rendez-vous en React Native à Douala, avec Firebase.'],
                ['Chinedu Okafor', 160000, 7, 'React Native senior, je peux livrer vite.'],
            ],
            'retained' => 'Linda Ngo Bassa', 'provider' => 'orange_money', 'phone' => '+22376543210',
        ],
        [
            'author' => 'Kwame Asante', 'age' => 0.18, 'status' => 'in_progress', 'country' => null, 'budget' => 200000, 'days' => 12,
            'title' => 'Module de facturation en FCFA et en cedis pour une appli de logistique',
            'skills' => ['Laravel', 'PHP', 'PostgreSQL'],
            'description' => <<<'MD'
Notre application de logistique s'ouvre au Togo. Il faut un module de facturation qui gère le cedi (avec pesewas) et le franc CFA (sans centimes), avec des montants stockés en entiers.
MD,
            'proposals' => [
                ['Rodrigue Hounkpatin', 195000, 10, 'Je gère des montants multidevises dans plusieurs projets à Cotonou. Je propose une petite classe Money testée.'],
                ['Sitsofe Amouzou', 180000, 12, 'Je suis à Lomé, je connais les exigences de facturation togolaises.'],
            ],
            'retained' => 'Rodrigue Hounkpatin', 'provider' => 'moov_money', 'phone' => '+233244556677',
        ],
        [
            'author' => 'Fatou Ndiaye', 'age' => 0.1, 'status' => 'in_progress', 'country' => 'SN', 'budget' => 110000, 'days' => 6,
            'title' => 'Audit d\'accessibilité et de performance d\'une application Next.js',
            'skills' => ['Next.js', 'React', '3G'],
            'description' => <<<'MD'
Avant le lancement, je veux un regard extérieur : audit d'accessibilité (contrastes, clavier, lecteurs d'écran) et de performance en 3G, avec une liste de corrections priorisées.
MD,
            'proposals' => [
                ['Kadiatou Diallo', 100000, 5, 'Les connexions lentes, c\'est mon quotidien à Labé. Je mesure en profil 3G et je priorise les corrections.'],
            ],
            'retained' => 'Kadiatou Diallo', 'provider' => 'free_money', 'phone' => '+221761234567',
        ],
        // Ouvertes, avec ou sans propositions.
        [
            'author' => 'Rodrigue Hounkpatin', 'age' => 0.08, 'status' => 'open', 'country' => 'BJ', 'budget' => 220000, 'days' => 14,
            'title' => 'Renfort Laravel pour deux semaines sur une plateforme de transport',
            'skills' => ['Laravel', 'Livewire', 'PostgreSQL'],
            'description' => <<<'MD'
Un client accélère son calendrier : je cherche un dev Laravel pour deux semaines, sur des fonctionnalités bien découpées (réservation, annulation, remboursement). Code revu par mon équipe.
MD,
            'proposals' => [
                ['Sitsofe Amouzou', 210000, 14, 'Laravel et Livewire tous les jours, disponible tout de suite.'],
                ['Ibrahima Sow', 120000, 14, 'Je suis encore étudiant mais je travaille sur Laravel depuis un an, ce serait une belle occasion d\'apprendre.'],
            ],
        ],
        [
            'author' => 'Wanjiru Kamau', 'age' => 0.05, 'status' => 'open', 'country' => null, 'budget' => 160000, 'days' => 10,
            'title' => 'Adaptateur Wave pour une API de paiement multi-opérateurs',
            'skills' => ['Wave', 'Node.js', 'TypeScript'],
            'description' => <<<'MD'
Notre API gère déjà M-Pesa et MTN MoMo derrière une interface commune. Je cherche un dev qui connaît Wave pour écrire l'adaptateur : création de session, webhook signé, statuts.
MD,
            'proposals' => [
                ['Moussa Diop', 150000, 7, 'Wave de bout en bout, c\'est mon quotidien. Je m\'aligne sur votre interface existante.'],
            ],
        ],
        [
            'author' => 'Salma Benali', 'age' => 0.03, 'status' => 'open', 'country' => 'MA', 'budget' => 130000, 'days' => 8,
            'title' => 'Tests automatisés pour une API Spring Boot existante',
            'skills' => ['Spring Boot', 'Java'],
            'description' => <<<'MD'
Une API Spring Boot en production n'a presque aucun test. Je cherche quelqu'un pour écrire les tests d'intégration des parcours principaux, avec une base PostgreSQL de test.
MD,
        ],
        [
            'author' => 'Edem Agbeko', 'age' => 0.02, 'status' => 'open', 'country' => 'TG', 'budget' => 80000, 'days' => 5,
            'title' => 'Petit tableau de bord pour les données d\'une ONG de santé',
            'skills' => ['Python', 'Data science'],
            'description' => <<<'MD'
J'ai les données nettoyées (pandas). Je cherche quelqu'un pour un tableau de bord web simple et léger, lisible sur téléphone, pour les responsables de l'ONG.
MD,
            'proposals' => [
                ['Hery Rakotomalala', 75000, 5, 'Python au quotidien, je peux faire un tableau de bord léger sans framework JavaScript lourd.'],
            ],
        ],
        // Annulée.
        [
            'author' => 'Ousmane Issoufou', 'age' => 0.33, 'status' => 'cancelled', 'country' => 'NE', 'budget' => 90000, 'days' => 7,
            'title' => 'Passerelle SMS pour les alertes météo des éleveurs',
            'skills' => ['SMS', 'PHP'],
            'description' => <<<'MD'
Je cherchais de l'aide pour la passerelle d'envoi groupé des alertes SMS. Le partenaire a finalement fourni son propre outil : mission annulée.
MD,
            'proposals' => [
                ['Chinedu Okafor', 85000, 6, 'J\'ai déjà fait des envois groupés avec Africa\'s Talking.'],
            ],
            'retained' => 'Chinedu Okafor', 'provider' => 'moov_money', 'phone' => '+22790123456',
        ],
    ],
];
