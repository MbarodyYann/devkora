<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;

/**
 * Horloge des données de démo : tout se passe dans la fenêtre [max(début du concours, maintenant − 30 j), maintenant],
 * relative au moment du semis (comme le fera demo:reset). La démo ne paraît jamais figée ni antérieure au concours.
 */
class DemoClock
{
    public const CONTEST_START = '2026-09-29';

    public const WINDOW_DAYS = 30;

    public function __construct(public readonly Carbon $now) {}

    public static function make(): self
    {
        return new self(Carbon::now());
    }

    public function start(): Carbon
    {
        return Carbon::parse(self::CONTEST_START, $this->now->getTimezone())->startOfDay()
            ->max($this->now->copy()->subDays(self::WINDOW_DAYS))
            ->min($this->now);
    }

    /**
     * Instant situé à la fraction $age de la fenêtre en partant de maintenant : 0 = maintenant, 1 = début de la fenêtre.
     * En journée (8 h à 20 h) quand ce n'est pas aujourd'hui, jamais hors de la fenêtre.
     */
    public function ago(float $age, int $seed = 0): Carbon
    {
        $span = $this->start()->diffInSeconds($this->now);
        $moment = $this->now->copy()->subSeconds((int) round($span * max(0, min(1, $age))));

        if (! $moment->isSameDay($this->now)) {
            $moment->setTime(8 + $seed % 12, ($seed * 17) % 60);
        }

        return $this->clamp($moment);
    }

    /** Ramène un instant dans la fenêtre. */
    public function clamp(Carbon $moment): Carbon
    {
        return $moment->max($this->start())->min($this->now);
    }
}
