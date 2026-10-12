<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Article;
use App\Models\Event;
use App\Models\Listing;
use App\Models\Profile;
use App\Models\Question;
use App\Services\Contributors;
use App\Support\Countries;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Durée de cache des chiffres de la bande de confiance (10 minutes). */
    public const STATS_TTL = 600;

    public function __invoke(Contributors $contributors): View
    {
        // Chiffres réels, sans arrondi (08, « Bande de confiance »).
        $stats = Cache::remember('home.stats', self::STATS_TTL, function () {
            $countries = Profile::query()->distinct()->pluck('country')->all();

            return [
                'devs' => Profile::query()->count(),
                'resolved' => Question::query()->visible()->whereNotNull('accepted_answer_id')->count(),
                'articles' => Article::query()->visible()->count(),
                'countries' => count($countries),
                // Drapeaux dans l'ordre de config/countries.php (pays francophones d'abord).
                'flags' => array_values(array_map(
                    Countries::flag(...),
                    array_keys(array_intersect_key(Countries::all(), array_flip($countries))),
                )),
            ];
        });

        // Questions du moment (08, « Sections suivantes de l'accueil ») : 14 derniers jours, 30 s'il y en a moins de 5.
        $trendingIds = Cache::remember('home.trending', self::STATS_TTL, function () {
            foreach ([14, 30] as $days) {
                $ids = Question::query()->trending($days)->limit(5)->pluck('id')->all();

                if (count($ids) >= 5) {
                    break;
                }
            }

            return $ids;
        });

        return view('home', [
            'stats' => $stats,
            'trendingQuestions' => Question::query()->visible()->with(['user.profile', 'skills'])->findMany($trendingIds)
                ->sortBy(fn (Question $question) => array_search($question->id, $trendingIds, true))->values(),
            // Annonces ouvertes (08) : équipes et missions mélangées, les plus récentes.
            'openListings' => Listing::query()->visible()->where('status', ListingStatus::Open)
                ->with(['user.profile', 'skills', 'acceptedProposals.user.profile'])->withCount('proposals')
                ->latest()->limit(3)->get(),
            'upcomingEvents' => Event::query()->visible()->where('ends_at', '>=', now())->orderBy('starts_at')
                ->with(['participants' => fn ($q) => $q->with('profile')->limit(50)])->withCount('participants')->limit(3)->get(),
            'contributors' => $contributors->ofMonth(5),
            'latestArticles' => Article::query()->visible()->with('user.profile')->latest()->limit(3)->get(),
        ]);
    }
}
