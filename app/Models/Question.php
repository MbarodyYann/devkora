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
}
