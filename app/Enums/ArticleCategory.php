<?php

namespace App\Enums;

enum ArticleCategory: string
{
    case Tutorial = 'tutorial';
    case Experience = 'experience';
    case BestPractice = 'best_practice';
    case ProjectStory = 'project_story';

    public function label(): string
    {
        return match ($this) {
            self::Tutorial => 'Tutoriel',
            self::Experience => 'Retour d\'expérience',
            self::BestPractice => 'Bonnes pratiques',
            self::ProjectStory => 'Retour de projet',
        };
    }

    /** Pluriel des filtres de /articles. */
    public function plural(): string
    {
        return match ($this) {
            self::Tutorial => 'Tutoriels',
            self::Experience => 'Retours d\'expérience',
            self::BestPractice => 'Bonnes pratiques',
            self::ProjectStory => 'Retours de projet',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
