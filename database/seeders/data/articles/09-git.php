<?php

return [
    'author' => 'Jean-Paul Habimana', 'category' => 'best_practice', 'age' => 0.36, 'useful' => 9,
    'title' => 'Git en équipe à distance : les règles qui nous évitent les drames',
    'tags' => ['Git', 'Open source'],
    'body' => <<<'MD'
Je maintiens une bibliothèque open source avec des contributeurs de Kigali, Nairobi, Kinshasa et Dakar. Nous ne sommes jamais dans la même pièce, rarement connectés en même temps, et nos connexions vont de la fibre au partage de connexion d'un téléphone. Au fil des années, nous avons adopté quelques règles Git simples. Elles ne sont pas originales, mais elles nous évitent la plupart des drames.

## 1. La branche principale est toujours livrable

Personne ne pousse directement sur `main`. Chaque changement passe par une branche, puis une demande de fusion (pull request) relue par quelqu'un d'autre. Sur GitHub ou GitLab, protège la branche pour que la règle soit appliquée par l'outil, pas par la bonne volonté.

Conséquence : n'importe qui peut cloner `main` à n'importe quel moment et obtenir une version qui fonctionne.

## 2. Des branches courtes, des demandes de fusion petites

Une branche qui vit trois semaines diverge de `main`, accumule les conflits, et devient impossible à relire. Notre règle : **une demande de fusion fait une seule chose**, et se relit en moins de quinze minutes. Au-delà de 400 lignes modifiées, on découpe.

Nommer les branches aide aussi tout le monde à s'y retrouver :

```bash
git switch -c correctif/numero-ivoirien-10-chiffres
git switch -c fonction/validation-numero-rwanda
```

## 3. Se mettre à jour avec `rebase`, fusionner avec une demande

Avant d'ouvrir ta demande de fusion, mets ta branche à jour par-dessus `main` :

```bash
git fetch origin
git rebase origin/main
# résoudre les éventuels conflits, puis :
git push --force-with-lease
```

`--force-with-lease` est la version prudente de `--force` : elle refuse d'écraser la branche distante si quelqu'un d'autre y a poussé entre-temps. N'utilise jamais `--force` tout court sur une branche partagée.

Et ne rebase **jamais** `main` ni une branche sur laquelle d'autres travaillent : réécrire un historique déjà récupéré par les autres, c'est la garantie d'une matinée perdue.

## 4. Des messages de commit utiles

Dans six mois, quelqu'un lira `git log` pour comprendre pourquoi une ligne existe. « fix » ou « mise à jour » ne l'aideront pas. Un bon message dit **ce qui change et pourquoi** :

```
fix: accepter les numéros ivoiriens à 10 chiffres

Depuis le 31 janvier 2021, les numéros de Côte d'Ivoire ont 10 chiffres.
Les anciens numéros à 8 chiffres sont refusés avec un message explicite.
```

Nous suivons les préfixes des Conventional Commits (`feat`, `fix`, `docs`, `test`) : ils rendent l'historique lisible d'un coup d'œil et permettent de générer les notes de version.

## 5. L'intégration continue fait la police

Une demande de fusion ne peut être fusionnée que si les tests passent. C'est l'outil qui le vérifie, pas la personne qui relit. Un fichier GitHub Actions minimal :

```yaml
name: Tests
on: [pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: npm
      - run: npm ci
      - run: npm test
```

Avantage inattendu pour une équipe aux connexions inégales : c'est le serveur d'intégration, pas ta connexion, qui télécharge les dépendances et lance les tests.

## 6. Relire avec bienveillance, et par écrit

La relecture est le moment où l'on apprend le plus. Quelques habitudes :

- **Commenter le code, pas la personne.** « Cette boucle fait une requête par élément » plutôt que « tu as fait une erreur ».
- **Distinguer le bloquant du suggéré.** Nous préfixons les remarques non bloquantes par « Suggestion : ».
- **Répondre dans les 48 heures.** Une demande de fusion qui attend une semaine décourage les contributeurs, surtout les nouveaux.

Tout se passe par écrit, dans la demande de fusion. Personne n'a besoin d'être en ligne au même moment, et la discussion reste pour ceux qui viendront après.

## 7. Les petites commandes qui sauvent

```bash
git stash            # mettre de côté un travail en cours
git switch -         # revenir à la branche précédente
git reflog           # retrouver un commit « perdu » après une fausse manœuvre
git commit --amend   # corriger le dernier commit avant de le pousser
git bisect start     # trouver le commit qui a introduit un bug
```

`git reflog` mérite une mention spéciale : il garde la trace de tout ce que tu as fait localement. Presque rien n'est vraiment perdu dans Git, tant que tu ne paniques pas.

## 8. Penser aux connexions lentes

- `git clone --depth 1` pour récupérer un gros dépôt sans tout son historique.
- Ne jamais commiter de fichiers lourds (vidéos, exports de base, `node_modules`) : chaque contributeur les téléchargerait pour toujours. Un `.gitignore` soigné dès le premier jour.
- Pour les fichiers binaires indispensables, regarder Git LFS.

## En résumé

Ces règles ne demandent aucun outil exotique. Elles demandent surtout que toute l'équipe les connaisse et les applique. Écris-les dans un fichier `CONTRIBUTING.md` à la racine du dépôt : c'est la première chose que lira un nouveau contributeur, qu'il soit à Kigali ou à Abidjan.
MD,
];
