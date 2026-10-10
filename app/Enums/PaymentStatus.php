<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Held = 'held';
    case Released = 'released';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Held => 'Fonds sécurisés',
            self::Released => 'Paiement libéré',
            self::Refunded => 'Remboursé',
        };
    }
}
