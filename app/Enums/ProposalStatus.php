<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(ListingType $type): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => $type === ListingType::Team ? 'Dans l\'équipe' : 'Retenue',
            self::Declined => 'Non retenue',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warm',
            self::Accepted => 'done',
            self::Declined => 'muted',
        };
    }
}
