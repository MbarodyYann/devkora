<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\ReputationReason;
use App\Models\Listing;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Avis de l'auteur sur le dev retenu, une fois la mission terminée, une seule fois.
 * Bonus de cauris selon la note (02) : 5 étoiles +30, 4 +20, 3 +10.
 */
class ReviewService
{
    public function __construct(private readonly ReputationService $reputation) {}

    /**
     * @param  array{rating: int, communication: int, quality: int, timeliness: int, comment?: string|null}  $data
     */
    public function create(Listing $listing, array $data): Review
    {
        return DB::transaction(function () use ($listing, $data) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($listing->id);
            if ($listing->status !== ListingStatus::Completed || $listing->review()->exists()) {
                throw new LogicException('Cette mission ne peut pas (ou plus) être notée.');
            }

            $dev = $listing->acceptedProposals()->with('user')->sole()->user;

            $review = new Review($data);
            $review->forceFill([
                'listing_id' => $listing->id,
                'reviewer_id' => $listing->user_id,
                'reviewee_id' => $dev->id,
            ])->save();

            if ($bonus = ReputationReason::reviewBonus($review->rating)) {
                $this->reputation->award($dev, ReputationReason::ReviewBonus, $review, $bonus);
            }

            return $review;
        });
    }
}
