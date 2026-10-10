<?php

/*
| Réponses complémentaires, rattachées à une question par son titre exact (voir QuestionSeeder).
| Elles arrivent après les réponses principales.
*/

return [
    'Webhook Wave : comment vérifier la signature dans Laravel ?' => [
        ['author' => 'Mamadou Bah', 'votes' => 2, 'after' => 26, 'body' => <<<'MD'
Pour tester en local, expose ton serveur avec un tunnel (Cloudflare Tunnel, ngrok…) et déclare cette URL dans le tableau de bord Wave. Pense à remettre l'URL de production après : je me suis fait avoir une fois.
MD],
    ],
    'Mon menu USSD se perd entre les étapes, comment garder l\'état ?' => [
        ['author' => 'Koffi Mensah', 'votes' => 3, 'after' => 12, 'body' => <<<'MD'
Petit test très utile : le simulateur d'Africa's Talking permet de parcourir ton menu dans le navigateur, sans téléphone ni crédit. Écris aussi un test automatique par chemin du menu (`''`, `'1'`, `'1*500'`, `'1*500*1'`), le `match` de Christian s'y prête bien.
MD],
    ],
    'React : mon useEffect s\'exécute deux fois au chargement' => [
        ['author' => 'Chinedu Okafor', 'votes' => 1, 'after' => 15, 'body' => <<<'MD'
Ne retire pas `<StrictMode>` pour « corriger » le problème : il te signale de vrais oublis de nettoyage (abonnements, minuteries) qui finiraient en fuites de mémoire.
MD],
    ],
    'Orange Money Web Payment : quelle devise utiliser en sandbox ?' => [
        ['author' => 'Yao Kouassi', 'votes' => 2, 'after' => 28, 'body' => <<<'MD'
Le jeton OAuth expire au bout d'une heure environ : mets-le en cache (`Cache::remember` avec une durée un peu plus courte que `expires_in`) au lieu d'en demander un nouveau à chaque paiement.
MD],
    ],
    'Comment rendre une page Laravel vraiment légère pour la 3G ?' => [
        ['author' => 'Abdoul Kaboré', 'votes' => 3, 'after' => 34, 'body' => <<<'MD'
Pour répondre à Junior : si serveur et utilisateurs sont à Dakar, un CDN étranger peut même ajouter de la latence. Il aide surtout pour les fichiers statiques et la protection contre les attaques. Mesure avant de décider.
MD],
    ],
    'Flutter : faut-il apprendre Provider, Riverpod ou Bloc en premier ?' => [
        ['author' => 'Ama Owusu', 'votes' => 2, 'after' => 6, 'body' => <<<'MD'
Je débute aussi et j'ai suivi le même conseil : `setState` d'abord, puis Riverpod. La documentation officielle de Riverpod a de bons exemples, et les messages d'erreur sont clairs.
MD],
        ['author' => 'Linda Ngo Bassa', 'votes' => 1, 'after' => 10, 'body' => <<<'MD'
Quel que soit ton choix, sépare dès le début l'interface et l'accès aux données (une classe `PrisesRepository`). Changer de gestion d'état plus tard sera alors facile.
MD],
    ],
    'MTN MoMo sandbox : erreur 500 sur requesttopay' => [
        ['author' => 'Linda Ngo Bassa', 'votes' => 2, 'after' => 22, 'body' => <<<'MD'
Les numéros de test de la sandbox déclenchent des résultats prévus : certains numéros simulent un refus ou un délai dépassé. Pratique pour tester tes écrans d'erreur sans attendre la production.
MD],
    ],
    'pandas : mes montants en FCFA sont lus comme du texte' => [
        ['author' => 'Otieno Odhiambo', 'votes' => 2, 'after' => 8, 'body' => <<<'MD'
Si le fichier vient d'un Excel français, essaie aussi `encoding="cp1252"` quand tu vois des caractères bizarres à la place des accents dans les noms de colonnes.
MD],
        ['author' => 'Hery Rakotomalala', 'votes' => 1, 'after' => 16, 'body' => <<<'MD'
Tu peux aussi lire directement le fichier Excel avec `pd.read_excel("dons.xlsx")` : les nombres restent des nombres, sans passer par le CSV.
MD],
    ],
    'Déployer Laravel sur un petit VPS de 1 Go : la configuration qui tient' => [
        ['author' => 'Moussa Diop', 'votes' => 2, 'after' => 40, 'body' => <<<'MD'
Et compile tes assets (`npm run build`) sur ta machine ou dans la CI, pas sur le serveur : Vite et Node consomment facilement plus de 500 Mo pendant le build.
MD],
    ],
    'Passer de WordPress à Laravel : par quoi commencer ?' => [
        ['author' => 'Mamadou Bah', 'votes' => 1, 'after' => 24, 'body' => <<<'MD'
J'ai fait le même passage. Le plus dur a été d'oublier les hooks de WordPress pour penser en routes, contrôleurs et modèles. Après deux petits projets, tout s'est éclairé.
MD],
    ],
    'Où stocker un jeton JWT dans une appli web : localStorage ou cookie ?' => [
        ['author' => 'Kadiatou Diallo', 'votes' => 2, 'after' => 6, 'body' => <<<'MD'
Et donne une durée de vie courte au jeton (15 minutes à quelques heures), avec un jeton de rafraîchissement lui aussi en cookie `HttpOnly`. Un jeton volé devient vite inutile.
MD],
    ],
    'Valider un numéro de téléphone camerounais et ivoirien côté formulaire' => [
        ['author' => 'Ousmane Issoufou', 'votes' => 2, 'after' => 9, 'body' => <<<'MD'
Côté serveur PHP, l'équivalent est `giggsey/libphonenumber-for-php`. Valide aussi côté serveur : la validation du formulaire se contourne facilement.
MD],
    ],
    'Comment présenter un paiement Wave pour que les gens aient confiance ?' => [
        ['author' => 'Moussa Diop', 'votes' => 3, 'after' => 12, 'body' => <<<'MD'
Côté technique, j'ajoute : ne marque jamais une commande « payée » sur la seule page de retour. L'utilisateur peut y arriver sans avoir payé. Seul le webhook vérifié fait foi.
MD],
    ],
    'Django : 300 requêtes SQL pour afficher une liste de cours' => [
        ['author' => 'Edem Agbeko', 'votes' => 1, 'after' => 14, 'body' => <<<'MD'
Merci pour `assertNumQueries`, je ne connaissais pas. Je l'ai ajouté à nos tests et il a déjà trouvé deux autres pages avec le même problème.
MD],
    ],
    'Laravel : quand utiliser une file d\'attente pour les paiements mobiles ?' => [
        ['author' => 'Sitsofe Amouzou', 'votes' => 1, 'after' => 26, 'body' => <<<'MD'
Rends tes jobs idempotents : s'il est relancé après une erreur, `ConfirmerPaiement` ne doit pas envoyer deux SMS ni créditer deux fois. Vérifie l'état de la commande avant d'agir.
MD],
    ],
    'Une seule interface pour Wave, Orange Money, MTN MoMo et M-Pesa ?' => [
        ['author' => 'Kwame Asante', 'votes' => 2, 'after' => 30, 'body' => <<<'MD'
Pour tester, écris un adaptateur `FauxOperateur` qui réussit ou échoue selon le montant (par exemple, échec si le montant finit par 13). Tes tests de bout en bout ne dépendent plus d'aucune sandbox.
MD],
    ],
    'Combien facturer une intégration Wave pour une PME à Dakar ?' => [
        ['author' => 'Awa Sarr', 'votes' => 2, 'after' => 15, 'body' => <<<'MD'
Montre au client ce qu'il gagne, pas seulement ce que ça coûte : moins de paiements à la livraison, moins d'argent liquide à gérer. Le prix passe beaucoup mieux.
MD],
    ],
    'Laravel Sanctum : erreur CORS entre mon interface Vue et l\'API' => [
        ['author' => 'Rodrigue Hounkpatin', 'votes' => 2, 'after' => 9, 'body' => <<<'MD'
En local, utilise le même domaine parent pour les deux (`app.test` et `api.app.test` avec Herd ou Valet) : tu reproduis exactement la configuration de production et tu évites les surprises au déploiement.
MD],
    ],
    'Livewire : ma recherche envoie une requête à chaque lettre tapée' => [
        ['author' => 'Kadiatou Diallo', 'votes' => 1, 'after' => 12, 'body' => <<<'MD'
Si la liste est petite (moins de quelques centaines de lignes), filtre directement dans le navigateur avec Alpine.js : zéro requête pendant la saisie.
MD],
    ],
    'Données vocales en wolof et en bambara : par où commencer ?' => [
        ['author' => 'Amine Trabelsi', 'votes' => 2, 'after' => 20, 'body' => <<<'MD'
Merci Mariam et Fatou ! Je prépare une liste de 200 phrases courtes (chiffres, montants, villes) pour une première collecte. Je publierai bientôt une annonce pour former une petite équipe.
MD],
    ],
    'Symfony : tester un service qui appelle une API externe' => [
        ['author' => 'Léa Moussavou', 'votes' => 1, 'after' => 18, 'body' => <<<'MD'
Merci Rodrigue, c'était exactement ça. Mes tests sont passés de 40 secondes à moins d'une seconde.
MD],
    ],
    'Tailwind CSS : comment gérer un mode sombre sans clignotement ?' => [
        ['author' => 'Junior Tchoupo', 'votes' => 1, 'after' => 8, 'body' => <<<'MD'
Si ton site a une politique de sécurité du contenu (CSP) stricte, ce script en ligne sera bloqué : ajoute-lui un nonce ou son empreinte SHA-256 dans l'en-tête CSP.
MD],
    ],
    'Hackathon : comment préparer une démo qui marche même sans Internet ?' => [
        ['author' => 'Rasmata Ouédraogo', 'votes' => 1, 'after' => 6, 'body' => <<<'MD'
Garde aussi une version APK installée sur deux téléphones, chargés à 100 %. Le jour J, ce n'est jamais le moment de compiler.
MD],
    ],
    'Sauvegarder une base PostgreSQL chaque nuit sur un petit serveur' => [
        ['author' => 'Koffi Mensah', 'votes' => 2, 'after' => 20, 'body' => <<<'MD'
Ajoute une alerte si la sauvegarde échoue (un simple e-mail ou SMS depuis le script avec `|| alerte.sh`) : une sauvegarde qui échoue en silence pendant trois semaines, ça arrive plus souvent qu'on ne croit.
MD],
    ],
];
