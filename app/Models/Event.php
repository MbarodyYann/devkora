<?php

namespace App\Models;

use App\Enums\EventType;
use App\Support\Countries;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Événement de la communauté (meetup, atelier, hackathon), en présentiel ou en ligne.
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Jamais fillables : user_id, description_html, excerpt, hidden_at.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'online',
        'city',
        'country',
        'registration_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'online' => 'boolean',
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
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Annonces « Cherche coéquipiers » liées (hackathon).
     *
     * @return HasMany<Listing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function isPast(): bool
    {
        return $this->ends_at->isPast();
    }

    /** « Dakar, Sénégal » ou « En ligne ». */
    public function placeLabel(): string
    {
        return $this->online ? 'En ligne' : trim($this->city.', '.Countries::name($this->country), ', ');
    }

    /** « jeu. 23 oct. 2026, 18 h 30 ». */
    public function whenLabel(): string
    {
        $start = $this->starts_at;
        $label = $start->translatedFormat('D j M Y, G \h i');

        if (! $this->ends_at->isSameDay($start)) {
            $label .= ' au '.$this->ends_at->translatedFormat('D j M');
        }

        return str_replace(' h 00', ' h', $label);
    }

    /** @param  Builder<Event>  $query */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull('events.hidden_at');
    }
}
