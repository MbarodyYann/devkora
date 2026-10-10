<?php

namespace App\Enums;

enum EventType: string
{
    case Meetup = 'meetup';
    case Workshop = 'workshop';
    case Hackathon = 'hackathon';

    public function label(): string
    {
        return match ($this) {
            self::Meetup => 'Meetup',
            self::Workshop => 'Atelier',
            self::Hackathon => 'Hackathon',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
