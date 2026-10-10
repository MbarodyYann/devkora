<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\ProposalStatus;
use App\Models\Concerns\Searchable;
use App\Support\Countries;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Annonce publiée par un dev pour d'autres devs : « Cherche coéquipiers » (team) ou « Mission entre devs » (mission).
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory, Searchable;

    /**
     * Jamais fillables : user_id, type, status, assigned_at, completed_at, hidden_at, description_html, excerpt.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'roles_wanted',
        'hours_per_week',
        'budget_fcfa',
        'delivery_days',
        'country',
        'deadline',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ListingType::class,
            'status' => ListingStatus::class,
            'budget_fcfa' => 'integer',
            'delivery_days' => 'integer',
            'hours_per_week' => 'integer',
            'deadline' => 'date',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function searchBodyColumn(): string
    {
        return 'description';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hackathon auquel l'équipe est liée.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    /**
     * Membres acceptés (équipe) ou dev retenu (mission).
     *
     * @return HasMany<Proposal, $this>
     */
    public function acceptedProposals(): HasMany
    {
        return $this->hasMany(Proposal::class)->where('status', ProposalStatus::Accepted);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * @return HasOne<Review, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('position')->orderByPivot('position');
    }

    public function isMission(): bool
    {
        return $this->type === ListingType::Mission;
    }

    public function isOpen(): bool
    {
        return $this->status === ListingStatus::Open;
    }

    /** « Dakar, Sénégal », ou « Télétravail ». */
    public function placeLabel(): string
    {
        return $this->country ? (string) Countries::name($this->country) : 'Télétravail';
    }

    /** @param  Builder<Listing>  $query */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull('listings.hidden_at');
    }
}
