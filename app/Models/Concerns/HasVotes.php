<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Contenu votable (question, réponse) : relation des votes et vote du membre connecté.
 */
trait HasVotes
{
    /**
     * @return MorphMany<Vote, $this>
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /**
     * Ajoute my_vote (+1, −1 ou null) sans requête par contenu.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithMyVote(Builder $query, ?User $user): void
    {
        if ($user) {
            $query->addSelect(['my_vote' => Vote::query()->select('value')
                ->whereColumn('votable_id', $this->qualifyColumn('id'))
                ->where('votable_type', $this->getMorphClass())
                ->where('user_id', $user->id)
                ->limit(1)]);
        }
    }

    /** @param  Builder<static>  $query */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('hidden_at'));
    }
}
