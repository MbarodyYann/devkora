<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Skill;
use App\Services\Contributors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Accueil : le fil des questions (composant Livewire Questions\Index) et sa colonne latérale.
 * Le visiteur cherche, lit et parcourt sans compte ; la présentation de DevKora est sur /a-propos.
 */
class HomeController extends Controller
{
    public function __invoke(Contributors $contributors): View
    {
        return view('feed', [
            // Tags locaux (Wave, Orange Money, USSD…) portés par au moins une question visible.
            'localTags' => Skill::query()->where('is_local', true)
                ->whereHas('questions', fn (Builder $q) => $q->whereNull('hidden_at'))
                ->orderBy('name')->get(['id', 'name', 'slug', 'is_local']),
            'upcomingEvents' => Event::query()->visible()->where('ends_at', '>=', now())->orderBy('starts_at')->limit(3)->get(),
            'openListings' => Listing::query()->visible()->where('status', ListingStatus::Open)->latest()->limit(3)->get(),
            'contributors' => $contributors->ofMonth(5),
        ]);
    }

    /** /questions : l'ancienne adresse du fil mène à l'accueil, avec ses filtres. */
    public function questions(Request $request): RedirectResponse
    {
        return redirect()->route('home', $request->query(), 301);
    }
}
