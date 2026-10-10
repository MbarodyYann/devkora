<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\ProposalStatus;
use App\Enums\ReputationReason;
use App\Support\Countries;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Le Pouls (02, P1) : les chiffres réels de la communauté, en cache 10 minutes.
 * Aucun chiffre inventé : un tarif n'est affiché qu'avec 3 données au moins.
 */
class PulseService
{
    public const CACHE_SECONDS = 600;

    public const MIN_SAMPLE = 3;

    /**
     * « Combien facturer ? » : médiane en FCFA par compétence, sur les budgets des missions entre devs
     * et les prix proposés par les devs.
     *
     * @return list<array{skill: string, slug: string, median: int, low: int, high: int, count: int}>
     */
    public function rates(): array
    {
        return Cache::remember('pulse.rates', self::CACHE_SECONDS, function () {
            $budgets = DB::table('listings')
                ->join('listing_skill', 'listing_skill.listing_id', '=', 'listings.id')
                ->where('listings.type', ListingType::Mission->value)
                ->where('listings.status', '!=', ListingStatus::Cancelled->value)
                ->whereNull('listings.hidden_at')
                ->select('listing_skill.skill_id', 'listings.budget_fcfa as amount');

            $proposals = DB::table('proposals')
                ->join('listings', 'listings.id', '=', 'proposals.listing_id')
                ->join('listing_skill', 'listing_skill.listing_id', '=', 'listings.id')
                ->where('listings.type', ListingType::Mission->value)
                ->whereNull('listings.hidden_at')
                ->whereNotNull('proposals.proposed_fcfa')
                ->select('listing_skill.skill_id', 'proposals.proposed_fcfa as amount');

            return DB::query()->fromSub($budgets->unionAll($proposals), 'amounts')
                ->join('skills', 'skills.id', '=', 'amounts.skill_id')
                ->groupBy('skills.id', 'skills.name', 'skills.slug')
                ->havingRaw('count(*) >= ?', [self::MIN_SAMPLE])
                ->selectRaw('skills.name as skill, skills.slug, count(*) as count')
                ->selectRaw('percentile_cont(0.5) within group (order by amount) as median')
                ->selectRaw('percentile_cont(0.25) within group (order by amount) as low')
                ->selectRaw('percentile_cont(0.75) within group (order by amount) as high')
                ->orderByDesc('count')->orderBy('skills.name')
                ->limit(12)
                ->get()
                ->map(fn ($row) => [
                    'skill' => $row->skill,
                    'slug' => $row->slug,
                    'median' => self::round($row->median),
                    'low' => self::round($row->low),
                    'high' => self::round($row->high),
                    'count' => (int) $row->count,
                ])
                ->all();
        });
    }

    /**
     * Compétences les plus demandées : annonces et questions, globalement ou dans un pays
     * (pays de l'annonce, ou du membre qui pose la question).
     *
     * @return list<array{skill: string, slug: string, count: int}>
     */
    public function demandedSkills(?string $country = null): array
    {
        return Cache::remember('pulse.demanded.'.($country ?? 'all'), self::CACHE_SECONDS, function () use ($country) {
            $listings = DB::table('listing_skill')
                ->join('listings', 'listings.id', '=', 'listing_skill.listing_id')
                ->whereNull('listings.hidden_at')
                ->when($country, fn ($q) => $q->where('listings.country', $country))
                ->select('listing_skill.skill_id');

            $questions = DB::table('question_skill')
                ->join('questions', 'questions.id', '=', 'question_skill.question_id')
                ->join('profiles', 'profiles.user_id', '=', 'questions.user_id')
                ->whereNull('questions.hidden_at')
                ->when($country, fn ($q) => $q->where('profiles.country', $country))
                ->select('question_skill.skill_id');

            return DB::query()->fromSub($listings->unionAll($questions), 'demand')
                ->join('skills', 'skills.id', '=', 'demand.skill_id')
                ->groupBy('skills.id', 'skills.name', 'skills.slug')
                ->selectRaw('skills.name as skill, skills.slug, count(*) as count')
                ->orderByDesc('count')->orderBy('skills.name')
                ->limit(10)
                ->get()
                ->map(fn ($row) => ['skill' => $row->skill, 'slug' => $row->slug, 'count' => (int) $row->count])
                ->all();
        });
    }

    /**
     * Pays où des questions ou des annonces existent (pour le sélecteur), dans l'ordre de config/countries.php.
     *
     * @return array<string, string>
     */
    public function demandCountries(): array
    {
        return Cache::remember('pulse.demand-countries', self::CACHE_SECONDS, function () {
            $codes = DB::table('listings')->whereNotNull('country')->whereNull('hidden_at')->distinct()->pluck('country')
                ->merge(DB::table('questions')->join('profiles', 'profiles.user_id', '=', 'questions.user_id')->whereNull('questions.hidden_at')->distinct()->pluck('profiles.country'))
                ->unique()->all();

            return array_intersect_key(Countries::all(), array_flip($codes));
        });
    }

    /**
     * Devs par pays, du plus grand nombre au plus petit.
     *
     * @return list<array{country: string, name: string, flag: string, count: int}>
     */
    public function devsByCountry(): array
    {
        return Cache::remember('pulse.devs-by-country', self::CACHE_SECONDS, fn () => DB::table('profiles')
            ->groupBy('country')
            ->selectRaw('country, count(*) as count')
            ->orderByDesc('count')->orderBy('country')
            ->get()
            ->map(fn ($row) => ['country' => $row->country, 'name' => (string) Countries::name($row->country), 'flag' => Countries::flag($row->country), 'count' => (int) $row->count])
            ->all());
    }

    /**
     * Activité des 30 derniers jours.
     *
     * @return array{resolved: int, articles: int, teams: int, missions: int}
     */
    public function activity(): array
    {
        return Cache::remember('pulse.activity', self::CACHE_SECONDS, function () {
            $since = now()->subDays(30);

            return [
                // Une question résolue = une réponse acceptée pendant la période (l'événement de cauris en porte la date ;
                // une réponse acceptée par l'auteur de la question lui-même ne compte pas).
                'resolved' => DB::table('reputation_events')->where('reason', ReputationReason::AnswerAccepted->value)->where('created_at', '>=', $since)->count(),
                'articles' => DB::table('articles')->whereNull('hidden_at')->where('created_at', '>=', $since)->count(),
                // Équipe formée : au moins un membre accepté pendant la période.
                'teams' => DB::table('listings')->where('type', ListingType::Team->value)->whereNull('hidden_at')
                    ->whereExists(fn ($q) => $q->from('proposals')->whereColumn('proposals.listing_id', 'listings.id')
                        ->where('proposals.status', ProposalStatus::Accepted->value)->where('proposals.decided_at', '>=', $since))
                    ->count(),
                'missions' => DB::table('listings')->where('status', ListingStatus::Completed->value)->where('completed_at', '>=', $since)->count(),
            ];
        });
    }

    /** Arrondi au millier : « 183 333 FCFA » n'aide personne. */
    private static function round(float|string|null $amount): int
    {
        return (int) (round((float) $amount / 1000) * 1000);
    }
}
