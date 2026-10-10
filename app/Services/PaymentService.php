<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\ProposalStatus;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Paiement Mobile Money SIMULÉ avec séquestre (02, P1) : aucun argent réel, aucun appel externe.
 * Retenir un dev = sécuriser les fonds ; ils sont libérés à la fin de la mission, ou remboursés si elle est annulée.
 */
class PaymentService
{
    public function __construct(private readonly Notifier $notifier) {}

    /** Code de confirmation simulé, à saisir par l'auteur (il s'affiche à l'écran, rien n'est envoyé). */
    public function confirmationCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Sécurise les fonds et retient le dev : la proposition est acceptée, les autres sont refusées,
     * la mission passe « en cours ».
     */
    public function secure(Listing $listing, Proposal $proposal, PaymentProvider $provider, string $phone): Payment
    {
        return DB::transaction(function () use ($listing, $proposal, $provider, $phone) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($listing->id);
            if (! $listing->isMission() || $listing->status !== ListingStatus::Open || $proposal->listing_id !== $listing->id || $proposal->status !== ProposalStatus::Pending) {
                throw new LogicException('Cette mission n\'attend plus de paiement.');
            }

            $payment = (new Payment)->forceFill([
                'listing_id' => $listing->id,
                'proposal_id' => $proposal->id,
                'provider' => $provider,
                'phone' => $phone,
                'amount_fcfa' => $proposal->proposed_fcfa,
                'status' => PaymentStatus::Held,
                'reference' => $this->reference(),
            ]);
            $payment->save();

            $proposal->forceFill(['status' => ProposalStatus::Accepted, 'decided_at' => now()])->save();
            $listing->proposals()->whereKeyNot($proposal->id)->where('status', ProposalStatus::Pending)
                ->update(['status' => ProposalStatus::Declined, 'decided_at' => now()]);
            $listing->forceFill(['status' => ListingStatus::InProgress, 'assigned_at' => now()])->save();
            $this->notifier->devRetained($listing, $proposal);

            return $payment;
        });
    }

    public function release(Listing $listing): void
    {
        $listing->payment()->where('status', PaymentStatus::Held)
            ->update(['status' => PaymentStatus::Released, 'released_at' => now()]);
    }

    public function refund(Listing $listing): void
    {
        $listing->payment()->where('status', PaymentStatus::Held)
            ->update(['status' => PaymentStatus::Refunded, 'refunded_at' => now()]);
    }

    /** Référence lisible et unique : DK-7Q2M9X. */
    private function reference(): string
    {
        do {
            $reference = 'DK-'.Str::upper(Str::random(6));
        } while (preg_match('/[^A-Z0-9-]/', $reference) || Payment::where('reference', $reference)->exists());

        return $reference;
    }
}
