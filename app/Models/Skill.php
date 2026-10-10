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
     * Retrouve les compétences par slug ou les crée, avec leur position (pivot) dans l'ordre donné.
     *
     * @param  list<string>  $names
     * @return array<int, array{position: int}>
     */
    public static function idsFor(array $names): array
    {
        return collect($names)
            ->mapWithKeys(fn (string $name, int $position) => [
                self::firstOrCreate(['slug' => self::slugFor($name)], ['name' => $name])->id => ['position' => $position],
            ])
            ->all();
    }

    /**
     * Tags saisis sous la forme « Laravel, Wave, USSD » : nettoyés et dédoublonnés.
     *
     * @return list<string>
     */
    public static function parseList(?string $input): array
    {
        return collect(explode(',', (string) $input))
            ->map(fn (string $name) => Str::squish($name))
            ->filter()
            ->unique(fn (string $name) => self::slugFor($name))
            ->values()
            ->all();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }

    /**
     * @return BelongsToMany<Article, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
