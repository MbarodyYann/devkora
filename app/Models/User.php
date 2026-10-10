<?php

namespace App\Models;

use App\Enums\Level;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Jamais fillables : reputation, is_admin, last_seen_at (03-architecture.md, « Sécurité »).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reputation' => 'integer',
            'is_admin' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<Profile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('position')->orderByPivot('position');
    }

    public function level(): Level
    {
        return Level::for($this->reputation);
    }

    /**
     * Dernière activité lisible (02, « Dernière activité ») : « Actif aujourd'hui »,
     * « Actif cette semaine ». Rien au-delà de 7 jours, ni si l'on ne sait pas.
     */
    public function activityLabel(): ?string
    {
        if ($this->last_seen_at === null) {
            return null;
        }

        return match (true) {
            $this->last_seen_at->isToday() => 'Actif aujourd\'hui',
            $this->last_seen_at->gte(now()->subDays(7)->startOfDay()) => 'Actif cette semaine',
            default => null,
        };
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /** Prénom, pour les textes (« Le profil de Moussa »). */
    public function firstName(): string
    {
        return Str::before(trim($this->name), ' ');
    }
}
