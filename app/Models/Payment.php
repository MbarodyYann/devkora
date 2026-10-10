<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement simulé d'une mission entre devs : fonds sécurisés, puis libérés ou remboursés.
 * Aucun argent réel, aucun appel externe. Écrit uniquement par PaymentService.
 */
class Payment extends Model
{
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentStatus::class,
            'amount_fcfa' => 'integer',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** Numéro masqué : « +221 77 ••• •• 12 ». */
    public function maskedPhone(): string
    {
        return self::mask($this->phone);
    }

    public static function mask(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return '+'.substr($digits, 0, 3).' '.substr($digits, 3, 2).' ••• •• '.substr($digits, -2);
    }
}
