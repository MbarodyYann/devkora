<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Models\Listing;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function show(Listing $listing): View
    {
        // Annonce masquée : une 404, comme si elle n'existait pas.
        abort_unless(Gate::allows('view', $listing), 404);

        $me = auth()->user();
        $isAuthor = $me?->id === $listing->user_id;
        $listing->load(['event', 'user.profile', 'skills', 'payment', 'review.reviewer', 'acceptedProposals.user.profile']);

        // L'auteur compare toutes les réponses ; un autre membre ne voit que la sienne.
        $proposals = $isAuthor
            ? $listing->proposals()
                ->with('user.profile')
                ->orderByRaw("case status when 'accepted' then 0 when 'pending' then 1 else 2 end")
                ->oldest()
                ->get()
            : collect();

        return view('listings.show', [
            'listing' => $listing,
            'isAuthor' => $isAuthor,
            'proposals' => $proposals,
            'myProposal' => $me && ! $isAuthor ? $listing->proposals()->where('user_id', $me->id)->first() : null,
            'pendingCount' => $isAuthor ? $proposals->where('status', ProposalStatus::Pending)->count() : 0,
        ]);
    }
}
