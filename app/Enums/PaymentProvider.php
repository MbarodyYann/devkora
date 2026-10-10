<?php

namespace App\Enums;

/** Opérateurs Mobile Money proposés pour la sécurisation simulée des fonds (02, P1). */
enum PaymentProvider: string
{
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case FreeMoney = 'free_money';
    case MtnMomo = 'mtn_momo';
    case MoovMoney = 'moov_money';

    public function label(): string
    {
        return match ($this) {
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::FreeMoney => 'Free Money',
            self::MtnMomo => 'MTN MoMo',
            self::MoovMoney => 'Moov Money',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $p) => [$p->value => $p->label()])->all();
    }
}
