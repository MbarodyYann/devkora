<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'is_local',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_local' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Skill $skill) {
            $skill->slug = $skill->slug ?: self::slugFor($skill->name);
        });
    }

    /** Slug d'une compétence : « C# » → « c-sharp », « C++ » → « c-plus-plus » (pas « c »). */
    public static function slugFor(string $name): string
    {
        return Str::slug(str_replace(['#', '+'], [' sharp', ' plus'], $name));
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
