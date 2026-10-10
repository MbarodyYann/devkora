<?php

return [
    'author' => 'Aïssatou Koné', 'category' => 'tutorial', 'age' => 0.58, 'useful' => 10,
    'title' => 'Flutter hors ligne d\'abord : une architecture qui tient sur le terrain',
    'tags' => ['Flutter', 'Hors ligne', 'Dart'],
    'body' => <<<'MD'
Mes applications servent des commerçantes du marché d'Adjamé, des agents de collecte et des livreurs. Leur point commun : le réseau va et vient toute la journée. Pour eux, une application qui affiche « Pas de connexion » est une application inutile. J'ai donc pris l'habitude de concevoir **hors ligne d'abord** : l'appli fonctionne sans réseau, et la synchronisation se fait quand elle peut.

## Le principe : la base locale est la source de vérité de l'écran

Dans une appli classique, l'écran demande les données au serveur et attend. Dans une appli hors ligne d'abord :

1. L'écran lit **toujours** la base locale (SQLite).
2. Chaque action de l'utilisateur est **écrite localement**, et ajoutée à une **file d'envoi**.
3. Un service de synchronisation vide la file vers le serveur dès que le réseau le permet, puis récupère les nouveautés.

L'utilisateur ne voit jamais d'attente. Il voit, au plus, un petit indicateur « 3 ventes en attente d'envoi ».

## 1. Des identifiants créés sur le téléphone

Si c'est le serveur qui attribue les identifiants, une vente créée hors ligne n'en a pas, et il devient impossible de la relier à autre chose. La solution : générer un **UUID** sur le téléphone.

```dart
import 'package:uuid/uuid.dart';

final vente = Vente(
  id: const Uuid().v4(),
  produitId: produit.id,
  montant: 2500,
  vendueLe: DateTime.now().toUtc(),
);
```

Côté serveur, cet UUID devient la clé : recevoir deux fois la même vente ne crée pas de doublon.

## 2. La base locale et la file d'envoi

J'utilise `drift` (au-dessus de SQLite), mais le principe est le même avec `sqflite`. Deux tables : les données métier, et les opérations en attente.

```dart
class Ventes extends Table {
  TextColumn get id => text()();
  TextColumn get produitId => text()();
  IntColumn get montant => integer()();
  DateTimeColumn get vendueLe => dateTime()();

  @override
  Set<Column> get primaryKey => {id};
}

class OperationsEnAttente extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get type => text()(); // 'vente.creee', 'vente.annulee'…
  TextColumn get payload => text()(); // JSON
  DateTimeColumn get creeeLe => dateTime()();
}
```

Enregistrer une vente écrit les deux, **dans la même transaction** : soit tout est enregistré, soit rien.

```dart
Future<void> enregistrerVente(Vente vente) {
  return db.transaction(() async {
    await db.into(db.ventes).insert(vente.toCompanion());
    await db.into(db.operationsEnAttente).insert(OperationsEnAttenteCompanion.insert(
      type: 'vente.creee',
      payload: jsonEncode(vente.toJson()),
      creeeLe: DateTime.now().toUtc(),
    ));
  });
}
```

## 3. La synchronisation

Le service de synchronisation envoie les opérations **par lots**. En 3G, une requête de 50 ventes coûte bien moins cher que 50 requêtes d'une vente.

```dart
class Synchronisation {
  Synchronisation(this.db, this.api);
  final AppDatabase db;
  final ApiClient api;
  bool _enCours = false;

  Future<void> lancer() async {
    if (_enCours) return;
    _enCours = true;
    try {
      while (true) {
        final lot = await (db.select(db.operationsEnAttente)
              ..orderBy([(o) => OrderingTerm.asc(o.id)])
              ..limit(50))
            .get();
        if (lot.isEmpty) break;

        final acceptees = await api.envoyer(lot); // le serveur renvoie les identifiants traités
        await (db.delete(db.operationsEnAttente)..where((o) => o.id.isIn(acceptees))).go();
        if (acceptees.length < lot.length) break; // on réessaiera plus tard
      }
    } on SocketException {
      // Pas de réseau : la file attendra la prochaine occasion.
    } finally {
      _enCours = false;
    }
  }
}
```

Le serveur répond avec la liste des opérations traitées : on ne supprime localement que celles-là. Une opération refusée (données invalides) doit être signalée à l'utilisateur, pas ignorée.

## 4. Quand lancer la synchronisation

- **Au retour du réseau**, avec `connectivity_plus`. Attention : être connecté à un réseau ne veut pas dire avoir Internet. L'envoi peut échouer quand même, et c'est prévu.
- **Après chaque action**, en tâche de fond, si le réseau est là.
- **Périodiquement, même appli fermée**, avec `workmanager` sur Android :

```dart
Workmanager().registerPeriodicTask(
  'synchronisation',
  'synchroniser',
  frequency: const Duration(minutes: 15),
  constraints: Constraints(networkType: NetworkType.connected),
);
```

## 5. Les conflits

Que se passe-t-il si deux téléphones modifient le même produit hors ligne ? Pour la plupart des applications de terrain, une règle simple suffit :

- **Les créations ne sont jamais en conflit** grâce aux UUID.
- **Pour les modifications**, la dernière écrite gagne, en comparant une date de modification envoyée par le téléphone.
- **Pour les données sensibles**, comme un stock ou un solde, on n'envoie pas l'état final mais l'**opération** : « −3 sacs de riz », pas « stock = 12 ». Le serveur applique les opérations dans l'ordre, et le total reste juste.

## 6. Montrer l'état à l'utilisateur, sans l'inquiéter

Un indicateur discret suffit : « Tout est envoyé » ou « 3 ventes en attente d'envoi ». Évite les messages d'erreur rouges pour une simple absence de réseau : c'est la situation normale, pas une panne.

## Ce que ça change

Depuis que nous travaillons ainsi, les commerçantes n'ont plus jamais perdu une vente, et les appels au support « l'appli ne marche pas » ont presque disparu. Concevoir hors ligne d'abord demande un peu plus de réflexion au départ, mais pour une grande partie de nos utilisateurs, c'est simplement la seule façon de faire une application qui marche.
MD,
];
