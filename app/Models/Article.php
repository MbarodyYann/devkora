<?php

namespace App\Models;

use App\Enums\ArticleCategory;
use App\Models\Concerns\HasVotes;
use App\Models\Concerns\Searchable;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, HasVotes, Searchable;

    /**
     * Jamais fillables : user_id, slug, score, hidden_at, body_html, excerpt, reading_minutes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category',
        'title',
        'body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'score' => 'integer',
            'reading_minutes' => 'integer',
            'hidden_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('position')->orderByPivot('position');
    }
}
