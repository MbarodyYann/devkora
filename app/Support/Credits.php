<?php

namespace App\Support;

/**
 * Licences des briques de DevKora, lues dans composer.lock et package-lock.json (page À propos).
 * Le règlement n'autorise que des licences permissives : aucune GPL, AGPL ou LGPL.
 */
class Credits
{
    /** Briques qui ne sont pas des paquets (polices, icônes, base de données). */
    public const EXTRA = [
        ['name' => 'Newsreader, Schibsted Grotesk, JetBrains Mono', 'license' => 'SIL OFL 1.1', 'role' => 'Polices, auto-hébergées'],
        ['name' => 'Lucide', 'license' => 'ISC', 'role' => 'Icônes'],
        ['name' => 'PostgreSQL', 'license' => 'PostgreSQL License', 'role' => 'Base de données'],
    ];

    /** Rôle affiché des dépendances directes. */
    private const ROLES = [
        'laravel/framework' => 'Framework', 'laravel/fortify' => 'Authentification', 'laravel/tinker' => 'Console',
        'livewire/livewire' => 'Interactivité', 'intervention/image' => 'Photos de profil', 'league/commonmark' => 'Markdown',
        'scrivo/highlight.php' => 'Coloration du code', 'alpinejs' => 'Interactivité légère', 'tailwindcss' => 'Styles',
        '@tailwindcss/vite' => 'Styles', 'vite' => 'Construction des fichiers', 'laravel-vite-plugin' => 'Construction des fichiers',
        'pestphp/pest' => 'Tests', 'pestphp/pest-plugin-laravel' => 'Tests', 'playwright' => 'Tests dans le navigateur',
        'laravel/pint' => 'Style du code', 'laravel/pail' => 'Journaux', 'fakerphp/faker' => 'Données de test',
        'mockery/mockery' => 'Tests', 'nunomaduro/collision' => 'Erreurs en console', 'concurrently' => 'Développement',
    ];

    /**
     * Dépendances directes, application d'abord (dev = outils de développement).
     *
     * @return list<array{name: string, version: string, license: string, role: string, dev: bool}>
     */
    public static function direct(): array
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
        $package = json_decode((string) file_get_contents(base_path('package.json')), true);
        $composerLock = self::composerLock();
        $npmLock = json_decode((string) file_get_contents(base_path('package-lock.json')), true)['packages'] ?? [];

        $rows = [];
        foreach (['require' => false, 'require-dev' => true] as $section => $dev) {
            foreach (array_keys($composer[$section] ?? []) as $name) {
                if ($name === 'php' || str_starts_with($name, 'ext-')) {
                    continue;
                }
                $rows[] = self::row($name, $composerLock[$name]['version'] ?? '', implode(' ou ', (array) ($composerLock[$name]['license'] ?? [])), $dev);
            }
        }
        foreach (['dependencies' => false, 'devDependencies' => true] as $section => $dev) {
            foreach (array_keys($package[$section] ?? []) as $name) {
                $lock = $npmLock["node_modules/$name"] ?? [];
                $rows[] = self::row($name, (string) ($lock['version'] ?? ''), (string) ($lock['license'] ?? ''), $dev);
            }
        }

        return $rows;
    }

    /**
     * Nombre de paquets (directs et indirects) et licences rencontrées, sur les deux fichiers de verrouillage.
     *
     * @return array{count: int, licenses: list<string>}
     */
    public static function summary(): array
    {
        $licenses = collect(self::composerLock())->pluck('license')->flatten()
            ->merge(collect(json_decode((string) file_get_contents(base_path('package-lock.json')), true)['packages'] ?? [])
                ->except([''])->pluck('license'));

        return [
            'count' => $licenses->count(),
            'licenses' => $licenses->filter()->map(fn ($l) => (string) $l)->unique()->sort()->values()->all(),
        ];
    }

    /**
     * @return array<string, array{version: string, license: list<string>}>
     */
    private static function composerLock(): array
    {
        $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);

        return collect([...$lock['packages'] ?? [], ...$lock['packages-dev'] ?? []])
            ->mapWithKeys(fn ($p) => [$p['name'] => ['version' => ltrim((string) $p['version'], 'v'), 'license' => self::chosen($p['license'] ?? [])]])
            ->all();
    }

    /**
     * Double licence (« BSD-3-Clause ou GPL ») : on utilise la licence permissive, la seule retenue.
     *
     * @param  list<string>  $licenses
     * @return list<string>
     */
    private static function chosen(array $licenses): array
    {
        $permissive = array_values(array_filter($licenses, fn (string $l) => ! preg_match('/GPL/i', $l)));

        return $permissive !== [] ? [$permissive[0]] : $licenses;
    }

    /**
     * @return array{name: string, version: string, license: string, role: string, dev: bool}
     */
    private static function row(string $name, string $version, string $license, bool $dev): array
    {
        return ['name' => $name, 'version' => ltrim($version, 'v'), 'license' => $license, 'role' => self::ROLES[$name] ?? '', 'dev' => $dev];
    }
}
