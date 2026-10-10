<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

/**
 * Estimateur de budget (02, P1) : budget médian des missions entre devs qui partagent au moins une compétence.
 * Au moins 3 missions comparables, sinon rien (pas de chiffre inventé).
 */
class BudgetEstimator
{
    public const MINIMUM_SAMPLE = 3;

    /**
     * @param  list<string>  $skillNames
     * @return array{median: int, count: int, label: string}|null
     */
    public function estimate(array $skillNames): ?array
    {
        $skills = Skill::query()->whereIn('slug', array_map(Skill::slugFor(...), $skillNames))->get(['id', 'name', 'slug']);
        if ($skills->isEmpty()) {
            return null;
        }

        $row = DB::table('listings')
            ->where('type', ListingType::Mission->value)
            ->whereNotIn('status', [ListingStatus::Cancelled->value])
            ->whereNull('hidden_at')
            ->whereExists(fn ($q) => $q->from('listing_skill')
                ->whereColumn('listing_skill.listing_id', 'listings.id')
                ->whereIn('listing_skill.skill_id', $skills->modelKeys()))
            ->selectRaw('count(*) as count, percentile_cont(0.5) within group (order by budget_fcfa) as median')
            ->first();

        if ($row === null || $row->count < self::MINIMUM_SAMPLE) {
            return null;
        }

        // Arrondi au millier : un médian « 183 333 FCFA » n'aide personne.
        return [
            'median' => (int) (round($row->median / 1000) * 1000),
            'count' => (int) $row->count,
            'label' => $skills->sortBy(fn ($s) => array_search($s->slug, array_map(Skill::slugFor(...), $skillNames)))->pluck('name')->take(2)->join(', '),
        ];
    }
}
