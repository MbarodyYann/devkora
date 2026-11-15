<?php

namespace App\Livewire\Events;

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Événements (/evenements) : à venir d'abord, puis passés ; filtres par lieu (ville ou en ligne) et par type.
 */
#[Title('Événements')]
#[Layout('components.layouts.app', ['pageHero' => [
    'start' => 'Rencontre les devs de ta ville,',
    'end' => 'ou en ligne.',
    'subtitle' => 'Meetups, ateliers et hackathons proposés par la communauté : inscris-toi, et trouve des coéquipiers pour les hackathons.',
    'action' => ['label' => 'Proposer un événement', 'route' => 'events.create'],
]])]
class Index extends Component
{
    public const ONLINE = 'en-ligne';

    #[Url(as: 'lieu', except: '')]
    public string $place = '';

    #[Url(except: '')]
    public string $type = '';

    public function resetFilters(): void
    {
        $this->reset('place', 'type');
    }

    /**
     * En ligne, puis les villes des événements, par ordre alphabétique.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function placeOptions(): array
    {
        return [self::ONLINE => 'En ligne'] + Event::query()->visible()->where('online', false)->distinct()->orderBy('city')
            ->pluck('city', 'city')->all();
    }

    /**
     * @return Collection<int, Event>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        return $this->query()->where('ends_at', '>=', now())->orderBy('starts_at')->get();
    }

    /**
     * @return Collection<int, Event>
     */
    #[Computed]
    public function past(): Collection
    {
        return $this->query()->where('ends_at', '<', now())->orderByDesc('starts_at')->limit(12)->get();
    }

    /** @return Builder<Event> */
    private function query(): Builder
    {
        return Event::query()->visible()
            ->when($this->place === self::ONLINE, fn (Builder $q) => $q->where('online', true))
            ->when($this->place !== '' && $this->place !== self::ONLINE, fn (Builder $q) => $q->where('online', false)->where('city', $this->place))
            ->when(EventType::tryFrom($this->type), fn (Builder $q, EventType $t) => $q->where('type', $t))
            ->with(['participants' => fn ($q) => $q->with('profile')->latest('event_user.created_at')->limit(50)])
            ->withCount('participants');
    }

    public function render(): View
    {
        return view('livewire.events.index', [
            'types' => ['' => 'Tous'] + EventType::options(),
        ]);
    }
}
