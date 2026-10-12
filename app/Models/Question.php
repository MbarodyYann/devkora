<?php

namespace App\Models;

use App\Models\Concerns\HasVotes;
use App\Models\Concerns\Searchable;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, HasVotes, Searchable;

    /**
     * Jamais fillables : user_id, score, answers_count, accepted_answer_id, hidden_at, body_html, excerpt.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'answers_count' => 'integer',
            'hidden_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Answer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * @return BelongsTo<Answer, $this>
     */
    public function acceptedAnswer(): BelongsTo
    {
        return $this->belongsTo(Answer::class, 'accepted_answer_id');
    }

    /**
     * Tags (compétences), dans l'ordre choisi par l'auteur.
     *
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('position')->orderByPivot('position');
    }

    public function isResolved(): bool
    {
        return $this->accepted_answer_id !== null;
    }

    /**
     * Recherche plein texte en français, sans accents (index GIN), les plus pertinentes d'abord.
     *
     * @param  Builder<Question>  $query
     */
    public function scopeSearch(Builder $query, string $terms): void
    {
        $query->fullTextSearch($terms);
    }

    /**
     * Questions du moment (accueil), classées comme Hacker News : (score + 2 × réponses + 1) / (heures + 2)^1,5.
     * Une question récente qui reçoit des votes et des réponses passe devant ; les questions sans réponse restent
     * dans la liste. L'âge est mesuré par rapport à now() de l'application (et non au NOW() de PostgreSQL) :
     * c'est l'horloge des données de démo (DemoClock) et des tests.
     *
     * @param  Builder<Question>  $query
     */
    public function scopeTrending(Builder $query, int $days = 14): void
    {
        $now = now()->toDateTimeString();

        $query->visible()
            ->whereBetween('created_at', [now()->subDays($days)->toDateTimeString(), $now])
            ->orderByRaw('(score + 2 * answers_count + 1) / power(extract(epoch from (?::timestamp - created_at)) / 3600 + 2, 1.5) desc', [$now])
            ->orderByDesc('id');
    }
}
