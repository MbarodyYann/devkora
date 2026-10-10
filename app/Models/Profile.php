<?php

namespace App\Models;

use App\Enums\Availability;
use App\Support\Countries;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'headline',
        'bio',
        'country',
        'city',
        'languages',
        'availability',
        'github_url',
        'portfolio_url',
        'avatar_path',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'availability' => 'collab',
        'languages' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availability' => Availability::class,
            'languages' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function countryName(): ?string
    {
        return Countries::name($this->country);
    }

    public function flag(): string
    {
        return Countries::flag($this->country);
    }

    /**
     * Langues parlées, en libellés, dans l'ordre de config/languages.php.
     *
     * @return list<string>
     */
    public function languageNames(): array
    {
        return collect(config('languages'))
            ->only($this->languages ?? [])
            ->values()
            ->all();
    }

    /** « Parle français, wolof » (null si aucune langue renseignée). */
    public function languagesLabel(): ?string
    {
        $names = $this->languageNames();

        return $names ? 'Parle '.implode(', ', $names) : null;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }
}
