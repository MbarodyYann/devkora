<?php

namespace App\Policies;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Règles des annonces (04, « Règles métier ») : on ne répond pas à sa propre annonce, une réponse par dev ;
 * seul l'auteur retient, termine, annule ou note ; une mission terminée ne s'annule plus ; un seul avis.
 */
class ListingPolicy
{
    public function view(?User $user, Listing $listing): bool
    {
        return $listing->hidden_at === null || $user?->id === $listing->user_id || ModerationPolicy::canModerate($user);
    }

    public function create(User $user): bool
    {
        return $user->profile()->exists();
    }

    public function propose(User $user, Listing $listing): Response
    {
        return match (true) {
            $user->id === $listing->user_id => Response::deny('Tu ne peux pas répondre à ta propre annonce.'),
            ! $listing->isOpen() || $listing->hidden_at !== null => Response::deny('Cette annonce n\'accepte plus de réponses.'),
            $listing->proposals()->where('user_id', $user->id)->exists() => Response::deny('Tu as déjà répondu à cette annonce.'),
            ! $user->profile()->exists() => Response::deny('Complète ton profil pour répondre.'),
            default => Response::allow(),
        };
    }

    /** Équipe ouverte : l'auteur accepte ou refuse les demandes, puis ferme l'annonce. */
    public function manageTeam(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id && $listing->type === ListingType::Team && $listing->isOpen();
    }

    /** Mission ouverte : l'auteur retient un dev (et sécurise les fonds). */
    public function hire(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id && $listing->isMission() && $listing->isOpen();
    }

    public function complete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id && $listing->isMission() && $listing->status === ListingStatus::InProgress;
    }

    public function cancel(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id && $listing->isMission()
            && in_array($listing->status, [ListingStatus::Open, ListingStatus::InProgress], true);
    }

    public function review(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id && $listing->status === ListingStatus::Completed && ! $listing->review()->exists();
    }
}
