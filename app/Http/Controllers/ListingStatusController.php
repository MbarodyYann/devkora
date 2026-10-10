<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ListingStatusController extends Controller
{
    /** Équipe complète. */
    public function close(Listing $listing, ListingService $listings): RedirectResponse
    {
        Gate::authorize('manageTeam', $listing);
        $listings->close($listing);

        return back()->with('status', 'Annonce fermée : ton équipe est complète. Bonne aventure !');
    }

    public function complete(Listing $listing, ListingService $listings): RedirectResponse
    {
        Gate::authorize('complete', $listing);
        $listings->complete($listing);

        return redirect()
            ->to(route('listings.show', $listing).'#avis')
            ->with('status', 'Mission terminée : le paiement est libéré et le dev reçoit +20 cauris. Laisse-lui un avis.');
    }

    public function cancel(Listing $listing, ListingService $listings): RedirectResponse
    {
        Gate::authorize('cancel', $listing);
        $refund = $listing->payment()->exists();
        $listings->cancel($listing);

        return redirect()
            ->route('listings.show', $listing)
            ->with('status', $refund ? 'Mission annulée : les fonds sécurisés te sont remboursés (simulation).' : 'Mission annulée.');
    }
}
