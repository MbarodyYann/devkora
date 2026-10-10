<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Http\Requests\StoreProposalRequest;
use App\Models\Listing;
use App\Models\Proposal;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProposalController extends Controller
{
    public function store(StoreProposalRequest $request, Listing $listing, ListingService $listings): RedirectResponse
    {
        $listings->propose(
            $request->user(),
            $listing,
            $request->validated('message'),
            $request->validated('proposed_fcfa') !== null ? (int) $request->validated('proposed_fcfa') : null,
            $request->validated('delivery_days') !== null ? (int) $request->validated('delivery_days') : null,
        );

        return redirect()
            ->to(route('listings.show', $listing).'#agir')
            ->with('status', $listing->isMission()
                ? 'Proposition envoyée. L\'auteur compare les propositions puis retient un dev.'
                : 'Demande envoyée. L\'auteur voit ton profil et ton message, puis t\'ajoute à l\'équipe.');
    }

    /** Équipe : accepter un membre. */
    public function accept(Listing $listing, Proposal $proposal, ListingService $listings): RedirectResponse
    {
        return $this->decide($listing, $proposal, $listings, true);
    }

    /** Équipe : refuser une demande. */
    public function decline(Listing $listing, Proposal $proposal, ListingService $listings): RedirectResponse
    {
        return $this->decide($listing, $proposal, $listings, false);
    }

    private function decide(Listing $listing, Proposal $proposal, ListingService $listings, bool $accept): RedirectResponse
    {
        Gate::authorize('manageTeam', $listing);
        abort_unless($proposal->listing_id === $listing->id && $proposal->status === ProposalStatus::Pending, 404);

        $listings->decideTeamRequest($proposal, $accept);

        return redirect()
            ->to(route('listings.show', $listing).'#equipe')
            ->with('status', $accept
                ? $proposal->user->firstName().' rejoint l\'équipe.'
                : 'Demande refusée.');
    }
}
