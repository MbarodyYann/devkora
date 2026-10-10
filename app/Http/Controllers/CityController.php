<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Question;
use App\Models\User;
use App\Support\Cities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Page d'une ville (/villes/{slug}) : ses devs, ses événements, les annonces et les questions de ses membres.
 */
class CityController extends Controller
{
    public function __invoke(string $slug): View
    {
        $city = Cities::find($slug) ?? abort(404);
        $inCity = fn (Builder $q) => $q->whereRaw('lower(f_unaccent(city)) = lower(f_unaccent(?))', [$city['name']]);

        return view('cities.show', [
            'city' => $city,
            'devs' => User::query()->whereHas('profile', $inCity)
                ->with(['profile', 'skills'])->orderByDesc('reputation')->orderBy('name')->limit(9)->get(),
            'devCount' => User::query()->whereHas('profile', $inCity)->count(),
            'events' => Event::query()->visible()->where('ends_at', '>=', now())->where($inCity)
                ->with(['participants' => fn ($q) => $q->with('profile')->limit(50)])->withCount('participants')->orderBy('starts_at')->limit(3)->get(),
            'listings' => Listing::query()->visible()->where('status', ListingStatus::Open)
                ->whereHas('user.profile', $inCity)
                ->with(['user.profile', 'skills', 'acceptedProposals.user.profile'])->withCount('proposals')->latest()->limit(3)->get(),
            'questions' => Question::query()->visible()->whereHas('user.profile', $inCity)
                ->with(['user.profile', 'skills'])->latest()->limit(4)->get(),
        ]);
    }
}
