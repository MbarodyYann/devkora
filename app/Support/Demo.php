<?php

namespace App\Support;

use App\Models\User;

/**
 * Mode démo (02-fonctionnalites.md, P2) : connexion en un clic avec trois comptes,
 * actif seulement si APP_DEMO=true (config devkora.demo).
 */
class Demo
{
    public const MOUSSA_EMAIL = 'moussa@demo.devkora.test';

    public const AISSATOU_EMAIL = 'aissatou@demo.devkora.test';

    public const KOFFI_EMAIL = 'koffi@demo.devkora.test';

    /**
     * Comptes de démonstration, dans l'ordre d'affichage.
     *
     * @var array<string, array{email: string, name: string, role: string}>
     */
    public const PERSONAS = [
        'moussa' => ['email' => self::MOUSSA_EMAIL, 'name' => 'Moussa', 'role' => 'dev Laravel à Dakar'],
        'aissatou' => ['email' => self::AISSATOU_EMAIL, 'name' => 'Aïssatou', 'role' => 'dev Flutter à Abidjan'],
        'koffi' => ['email' => self::KOFFI_EMAIL, 'name' => 'Koffi', 'role' => 'modérateur communautaire à Lomé'],
    ];

    public static function enabled(): bool
    {
        return (bool) config('devkora.demo');
    }

    /** Prénom du compte de démo connecté (bandeau du mode démo), sinon null. */
    public static function personaName(?User $user): ?string
    {
        if ($user === null || ! self::enabled()) {
            return null;
        }

        return collect(self::PERSONAS)->firstWhere('email', $user->email)['name'] ?? null;
    }
}
