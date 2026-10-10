<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Rubriques de la navigation (08, « Navigation flottante » et « Barre du bas mobile »), dans l'ordre.
 * Une rubrique n'apparaît que lorsque sa page existe : la navigation s'enrichit à chaque lot.
 */
class Navigation
{
    /** @var list<array{label: string, route: string, active: string, icon: string}> */
    private const ITEMS = [
        ['label' => 'Questions', 'route' => 'questions.index', 'active' => 'questions.*', 'icon' => 'message'],
        ['label' => 'Articles', 'route' => 'articles.index', 'active' => 'articles.*', 'icon' => 'book'],
        ['label' => 'Annonces', 'route' => 'listings.index', 'active' => 'listings.*', 'icon' => 'megaphone'],
        ['label' => 'Événements', 'route' => 'events.index', 'active' => 'events.*', 'icon' => 'calendar'],
        ['label' => 'Devs', 'route' => 'devs.index', 'active' => 'devs.*', 'icon' => 'users'],
        ['label' => 'Le Pouls', 'route' => 'pulse', 'active' => 'pulse', 'icon' => 'chart'],
    ];

    /**
     * Rubriques de la navigation du haut.
     *
     * @return list<array{label: string, route: string, active: string, icon: string}>
     */
    public static function main(): array
    {
        return array_values(array_filter(self::ITEMS, fn (array $item) => Route::has($item['route'])));
    }

    /**
     * Menu « Publier » des membres (08) : les actions de publication qui existent.
     *
     * @return list<array{label: string, route: string, icon: string}>
     */
    public static function publish(): array
    {
        return array_values(array_filter([
            ['label' => 'Poser une question', 'route' => 'questions.create', 'icon' => 'message'],
            ['label' => 'Écrire un article', 'route' => 'articles.create', 'icon' => 'book'],
            ['label' => 'Publier une annonce', 'route' => 'listings.create', 'icon' => 'megaphone'],
            ['label' => 'Proposer un événement', 'route' => 'events.create', 'icon' => 'calendar'],
        ], fn (array $item) => Route::has($item['route'])));
    }

    /**
     * Rubriques de la barre du bas mobile : 4 rubriques et « Moi » (ajouté par le gabarit). Les Événements et
     * Le Pouls n'y tiennent pas : ils sont accessibles depuis l'accueil, /annonces et le menu « Moi ».
     *
     * @return list<array{label: string, route: string, active: string, icon: string}>
     */
    public static function bottom(): array
    {
        return array_values(array_filter(self::main(), fn (array $item) => ! in_array($item['route'], ['pulse', 'events.index'], true)));
    }
}
