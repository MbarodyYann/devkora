<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\PaymentProvider;
use App\Models\Listing;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ListingService;
use App\Services\PaymentService;
use App\Services\ReviewService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Annonces de démonstration (data/listings.php) : équipes et missions à tous les stades, via les services
 * (cauris de fin de mission et d'avis issus des événements), datées dans la fenêtre de DemoClock.
 */
class ListingSeeder extends Seeder
{
    private DemoClock $clock;

    /** @var Collection<string, User> */
    private Collection $members;

    public function __construct(
        private readonly ListingService $listings,
        private readonly PaymentService $payments,
        private readonly ReviewService $reviews,
    ) {}

    /**
     * @return array{teams: list<array<string, mixed>>, missions: list<array<string, mixed>>}
     */
    public static function data(): array
    {
        return require __DIR__.'/data/listings.php';
    }

    public function run(): void
    {
        $this->clock = DemoClock::make();
        $this->members = User::query()->whereNotNull('last_seen_at')->get()->keyBy('name');
        $previousNow = Carbon::getTestNow();

        try {
            foreach (self::data()['teams'] as $index => $team) {
                $this->seedTeam($team, $index);
            }
            foreach (self::data()['missions'] as $index => $mission) {
                $this->seedMission($mission, $index);
            }
        } finally {
            Carbon::setTestNow($previousNow);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedTeam(array $data, int $index): void
    {
        $author = $this->member($data['author']);
        $at = $this->at($this->clock->ago($data['age'], $index + 7), $author);

        Carbon::setTestNow($at);
        $listing = $this->listings->publish($author, ListingType::Team, [
            'title' => $data['title'],
            'description' => $data['description'],
            'roles_wanted' => $data['roles'],
            'hours_per_week' => $data['hours'],
            'country' => $data['country'],
        ], $data['skills']);

        foreach ($data['members'] ?? [] as $i => [$name, $message]) {
            $proposal = $this->proposeAt($listing, $name, $message, $at->copy()->addHours(5 + 9 * $i));
            Carbon::setTestNow($this->clock->clamp($proposal->created_at->copy()->addHours(3)));
            $this->listings->decideTeamRequest($proposal, true);
        }
        foreach ($data['pending'] ?? [] as $i => [$name, $message]) {
            $this->proposeAt($listing, $name, $message, $at->copy()->addHours(20 + 11 * $i));
        }

        if ($data['closed'] ?? false) {
            Carbon::setTestNow($this->clock->clamp($at->copy()->addDays(2)));
            $this->listings->close($listing);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedMission(array $data, int $index): void
    {
        $author = $this->member($data['author']);
        $at = $this->at($this->clock->ago($data['age'], $index + 11), $author);

        Carbon::setTestNow($at);
        $listing = $this->listings->publish($author, ListingType::Mission, [
            'title' => $data['title'],
            'description' => $data['description'],
            'budget_fcfa' => $data['budget'],
            'delivery_days' => $data['days'],
            'country' => $data['country'],
        ], $data['skills']);

        $proposals = collect($data['proposals'] ?? [])->mapWithKeys(function (array $proposal, int $i) use ($listing, $at) {
            [$name, $price, $days, $message] = $proposal;

            return [$name => $this->proposeAt($listing, $name, $message, $at->copy()->addHours(3 + 7 * $i), $price, $days)];
        });

        if ($retained = $data['retained'] ?? null) {
            $hiredAt = $this->clock->clamp($proposals->max('created_at')->copy()->addHours(6));
            Carbon::setTestNow($hiredAt);
            $this->payments->secure($listing, $proposals[$retained], PaymentProvider::from($data['provider']), $data['phone']);

            if ($data['status'] === 'completed') {
                // Fin de mission avant le délai prévu, sans jamais dépasser maintenant.
                $doneAt = $this->clock->clamp($hiredAt->copy()->addHours((int) ($data['days'] * 12)))->min($this->clock->now->copy()->subHours(3));
                Carbon::setTestNow($doneAt);
                $this->listings->complete($listing);

                if ($review = $data['review'] ?? null) {
                    Carbon::setTestNow($this->clock->clamp($doneAt->copy()->addHours(2)));
                    [$rating, $communication, $quality, $timeliness, $comment] = $review;
                    $this->reviews->create($listing, compact('rating', 'communication', 'quality', 'timeliness', 'comment'));
                }
            }
        }

        if ($data['status'] === 'cancelled') {
            Carbon::setTestNow($this->clock->clamp($at->copy()->addDays(2)));
            $this->listings->cancel($listing);
        }
    }

    private function proposeAt(Listing $listing, string $name, string $message, Carbon $moment, ?int $price = null, ?int $days = null): Proposal
    {
        $dev = $this->member($name);
        Carbon::setTestNow($this->at($moment, $dev));

        return $this->listings->propose($dev, $listing, $message, $price, $days);
    }

    /** Dans la fenêtre, après l'inscription du membre (au moins 2 heures). */
    private function at(Carbon $moment, User $member): Carbon
    {
        return $this->clock->clamp($moment->max($member->created_at->copy()->addHours(2)));
    }

    private function member(string $name): User
    {
        return $this->members[$name] ?? throw new RuntimeException("Membre de démo inconnu : $name");
    }
}
