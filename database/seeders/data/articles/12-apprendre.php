<?php

return [
    'author' => 'Kadiatou Diallo', 'category' => 'experience', 'age' => 0.12, 'useful' => 7,
    'title' => 'Apprendre à coder avec une connexion limitée : ce qui a marché pour moi',
    'tags' => ['Python', 'Django', '3G'],
    'body' => <<<'MD'
J'ai appris à programmer à Labé, avec un ordinateur portable d'occasion et un forfait de données que je rechargeais par petits montants. Les tutoriels vidéo en haute définition, les environnements en ligne qui chargent tout depuis un serveur lointain, les mises à jour de plusieurs gigaoctets : tout ce que les cours en ligne considèrent comme acquis me coûtait de l'argent, ou ne marchait pas. Voici ce qui m'a permis d'apprendre quand même. Si tu es dans la même situation, j'espère que ça t'aidera.

## 1. Télécharger une fois, apprendre longtemps

Ma règle : profiter de chaque bonne connexion (le Wi-Fi d'un centre, d'un café, de l'université) pour **télécharger ce dont j'aurai besoin les semaines suivantes**.

- **La documentation hors ligne.** DevDocs (devdocs.io) permet de télécharger la documentation de Python, Django, JavaScript, PostgreSQL et beaucoup d'autres, puis de la consulter sans connexion. C'est l'outil qui m'a le plus servi.
- **Les livres et cours en PDF**, plutôt que les vidéos. Un chapitre texte pèse quelques centaines de kilo-octets ; une vidéo de dix minutes, des dizaines de mégaoctets.
- **Les vidéos, en basse définition.** Quand une vidéo est indispensable, la qualité 360p suffit pour lire du code à l'écran.

## 2. Un environnement de travail local et léger

J'ai vite abandonné les éditeurs en ligne : ils demandent une connexion permanente. Tout tournait sur mon ordinateur :

- **VS Code**, installé une fois, avec seulement quelques extensions.
- **Python et Django en local**, avec SQLite pour commencer : aucun serveur de base de données à installer.
- **Un cache des paquets.** `pip` garde les paquets téléchargés : réinstaller un projet ne les retélécharge pas.

Pour un nouveau projet, je réutilisais un environnement existant plutôt que de tout réinstaller :

```bash
python -m venv ~/envs/django
source ~/envs/django/bin/activate
pip install django
# Pour chaque nouveau projet, le même environnement :
django-admin startproject bibliotheque
```

Quand j'ai eu plusieurs projets aux besoins différents, je suis passée à un environnement par projet, mais au début, la simplicité comptait plus.

## 3. Écrire beaucoup de code, regarder peu de vidéos

Les vidéos donnent l'impression d'apprendre. C'est en écrivant du code qu'on apprend vraiment, et ça ne consomme aucune donnée. Ma méthode :

1. Lire une notion dans la documentation (hors ligne).
2. Fermer la documentation et essayer de l'utiliser dans un petit programme.
3. Revenir à la documentation seulement quand je bloque.

Mon premier vrai projet était une application de gestion de la bibliothèque de mon lycée : des livres, des élèves, des emprunts. Rien d'original, mais un vrai problème, avec de vrais utilisateurs qui me signalaient les bugs.

## 4. Poser des questions, mais des bonnes

Quand je bloquais, je n'avais pas le luxe de chercher pendant des heures en ligne. J'ai appris à préparer mes questions **hors ligne**, avant de me connecter :

- ce que je veux faire ;
- ce que j'ai essayé ;
- le message d'erreur complet ;
- le plus petit morceau de code qui reproduit le problème.

Souvent, en rédigeant la question, je trouvais la réponse toute seule. Sinon, je la publiais dès que j'avais du réseau, et je lisais les réponses à la connexion suivante.

## 5. Économiser les données au quotidien

Quelques réglages qui m'ont fait gagner beaucoup :

- **Désactiver les mises à jour automatiques** du système et des logiciels, et les faire sur une bonne connexion.
- **Bloquer la synchronisation** des services de stockage en ligne pendant que je travaille avec mon forfait.
- **Utiliser Git en local**, et ne pousser que quand j'ai une bonne connexion. Git fonctionne parfaitement sans réseau : commits, branches, historique, tout est sur ta machine.

```bash
git add .
git commit -m "Ajoute la recherche par titre"
# ... plus tard, sur une bonne connexion :
git push
```

## 6. Trouver une communauté

Le plus difficile n'a pas été la connexion, mais la solitude. À Labé, je ne connaissais personne qui programmait. Les groupes en ligne m'ont aidée, mais les réponses s'y perdaient vite dans le flot des messages.

Ce qui m'a le plus fait progresser : relire le code d'autres devs, et faire relire le mien. Une seule relecture par un dev plus expérimenté m'a appris plus qu'une semaine de tutoriels.

## Où j'en suis

Aujourd'hui, je développe une plateforme de cours en ligne pensée pour les connexions lentes : pages légères, contenus téléchargeables, vidéos optionnelles. Je construis l'outil que j'aurais aimé avoir.

Si tu apprends dans les mêmes conditions : ta connexion limite ta vitesse, pas ton niveau final. Télécharge, écris beaucoup de code, prépare tes questions, et trouve des gens avec qui avancer. Et quand tu auras appris, écris à ton tour : quelqu'un, quelque part, a besoin de ton article.
MD,
];
