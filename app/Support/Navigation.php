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
        ], fn (array $item) => Route::has($item['route'])));
    }

    /**
     * Rubriques de la barre du bas mobile (sans Le Pouls ; « Moi » est ajouté par le gabarit).
     *
     * @return list<array{label: string, route: string, active: string, icon: string}>
     */
    public static function bottom(): array
    {
        return array_values(array_filter(self::main(), fn (array $item) => $item['route'] !== 'pulse'));
    }
}
