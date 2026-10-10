<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\ProposalStatus;
use App\Enums\ReputationReason;
use App\Models\Listing;
use App\Models\Proposal;
use App\Models\Skill;
use App\Models\User;
use App\Support\French;
use App\Support\Text;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Cycle des annonces entre devs (02, « Communauté : annonces entre devs »).
 *
 * Équipe : ouverte → close (« Équipe complète »), l'auteur accepte plusieurs membres.
 * Mission : ouverte → en cours (fonds sécurisés, PaymentService) → terminée (paiement libéré, +20 cauris) ;
 * annulable tant qu'elle n'est pas terminée (remboursement simulé).
 */
class ListingService
{
    public function __construct(
        private readonly MarkdownRenderer $markdown,
        private readonly PaymentService $payments,
        private readonly ReputationService $reputation,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data  champs validés (titre, description, champs du type)
     * @param  list<string>  $skills
     */
    public function publish(User $author, ListingType $type, array $data, array $skills): Listing
    {
        return DB::transaction(function () use ($author, $type, $data, $skills) {
            $listing = new Listing(['title' => French::typography($data['title'])] + collect($data)->except('event_id')->all());
            $listing->forceFill([
                'user_id' => $author->id,
                'type' => $type,
                'status' => ListingStatus::Open,
                'description_html' => $this->markdown->toHtml($data['description']),
                'excerpt' => Text::excerpt($data['description']),
                // Équipe formée pour un hackathon de la communauté.
                'event_id' => $type === ListingType::Team ? ($data['event_id'] ?? null) : null,
            ])->save();

            $listing->skills()->sync(Skill::idsFor($skills));

            return $listing;
        });
    }

    public function propose(User $dev, Listing $listing, string $message, ?int $price = null, ?int $days = null): Proposal
    {
        $proposal = new Proposal(['message' => $message, 'proposed_fcfa' => $price, 'delivery_days' => $days]);
        $proposal->forceFill([
            'listing_id' => $listing->id,
            'user_id' => $dev->id,
            'status' => ProposalStatus::Pending,
        ])->save();

        $this->notifier->proposalReceived($proposal, $listing);

        return $proposal;
    }

    /** Équipe : accepter ou refuser une demande. */
    public function decideTeamRequest(Proposal $proposal, bool $accept): void
    {
        $proposal->forceFill([
            'status' => $accept ? ProposalStatus::Accepted : ProposalStatus::Declined,
            'decided_at' => now(),
        ])->save();

        $this->notifier->teamRequestDecided($proposal, $proposal->listing()->firstOrFail());
    }

    /** Équipe complète : l'annonce se ferme, les demandes en attente sont refusées. */
    public function close(Listing $listing): void
    {
        DB::transaction(function () use ($listing) {
            $listing->forceFill(['status' => ListingStatus::Closed])->save();
            $this->declinePending($listing);
        });
    }

    /** Fin de mission : paiement libéré, +20 cauris pour le dev retenu. */
    public function complete(Listing $listing): void
    {
        DB::transaction(function () use ($listing) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($listing->id);
            if ($listing->status !== ListingStatus::InProgress) {
                throw new LogicException('Seule une mission en cours peut être terminée.');
            }

            $listing->forceFill(['status' => ListingStatus::Completed, 'completed_at' => now()])->save();
            $this->payments->release($listing);

            $dev = $listing->acceptedProposals()->with('user')->sole()->user;
            $this->reputation->award($dev, ReputationReason::MissionCompleted, $listing);
            $this->notifier->missionCompleted($listing, $dev);
        });
    }

    /** Annulation (ouverte ou en cours) : fonds remboursés s'ils étaient sécurisés, demandes en attente refusées. */
    public function cancel(Listing $listing): void
    {
        DB::transaction(function () use ($listing) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($listing->id);
            if (! in_array($listing->status, [ListingStatus::Open, ListingStatus::InProgress], true)) {
                throw new LogicException('Une mission terminée ou déjà annulée ne s\'annule plus.');
            }

            $listing->forceFill(['status' => ListingStatus::Cancelled])->save();
            $this->payments->refund($listing);
            $this->declinePending($listing);
        });
    }

    private function declinePending(Listing $listing): void
    {
        $listing->proposals()->where('status', ProposalStatus::Pending)
            ->update(['status' => ProposalStatus::Declined, 'decided_at' => now()]);
    }
}
