<?php

namespace App\Livewire\Events;

use App\Enums\EventType;
use App\Services\EventService;
use App\Support\Countries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Proposer un événement (/evenements/proposer).
 */
#[Title('Proposer un événement')]
#[Layout('components.layouts.app', ['noindex' => true])]
class Create extends Component
{
    public string $type = '';

    public string $title = '';

    public string $description = '';

    public string $date = '';

    public string $startTime = '18:30';

    public string $endDate = '';

    public string $endTime = '21:00';

    public bool $online = false;

    public string $city = '';

    public string $country = '';

    public string $registrationUrl = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->profile()->exists(), 403);
        $this->city = auth()->user()->profile->city;
        $this->country = auth()->user()->profile->country;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(EventType::class)],
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'description' => ['required', 'string', 'min:60', 'max:6000'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'startTime' => ['required', 'date_format:H:i'],
            'endDate' => ['nullable', 'date', 'after_or_equal:date'],
            'endTime' => ['required', 'date_format:H:i'],
            'city' => $this->online ? ['nullable'] : ['required', 'string', 'max:80'],
            'country' => $this->online ? ['nullable'] : ['required', Rule::in(Countries::codes())],
            'registrationUrl' => ['nullable', 'url:https', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'type.required' => 'Choisis le type d\'événement.',
            'title.required' => 'Donne un titre à ton événement.',
            'title.min' => 'Ton titre est un peu court : dis de quoi il s\'agit.',
            'description.required' => 'Décris l\'événement : le programme, pour qui, ce qu\'il faut apporter.',
            'description.min' => 'Ajoute un peu de détail : le programme, pour qui, ce qu\'il faut apporter.',
            'date.required' => 'Choisis la date de l\'événement.',
            'date.after_or_equal' => 'La date doit être aujourd\'hui ou plus tard.',
            'endDate.after_or_equal' => 'La date de fin doit suivre la date de début.',
            'city.required' => 'Indique la ville, ou coche « En ligne ».',
            'country.required' => 'Choisis le pays, ou coche « En ligne ».',
            'registrationUrl.url' => 'Colle l\'adresse complète de l\'inscription, avec https://.',
        ];
    }

    public function publish(EventService $events)
    {
        $this->validate();

        $starts = Carbon::parse($this->date.' '.$this->startTime);
        $ends = Carbon::parse(($this->endDate ?: $this->date).' '.$this->endTime);
        if ($starts->isPast()) {
            $this->addError('startTime', 'L\'heure de début est déjà passée.');

            return null;
        }
        if ($ends->lte($starts)) {
            $this->addError('endTime', 'La fin doit être après le début.');

            return null;
        }

        if (! RateLimiter::attempt('events:'.auth()->id(), 5, fn () => true, 86400)) {
            $this->addError('title', 'Tu as proposé plusieurs événements aujourd\'hui : réessaie demain.');

            return null;
        }

        $event = $events->publish(auth()->user(), [
            'type' => EventType::from($this->type),
            'title' => trim($this->title),
            'description' => $this->description,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'online' => $this->online,
            'city' => $this->online ? null : trim($this->city),
            'country' => $this->online ? null : $this->country,
            'registration_url' => $this->registrationUrl ?: null,
        ]);

        session()->flash('status', 'Événement publié. Les devs intéressés peuvent dire qu\'ils participent.');

        return $this->redirectRoute('events.show', $event);
    }

    public function render(): View
    {
        return view('livewire.events.create', [
            'types' => EventType::options(),
            'countries' => Countries::grouped(),
        ]);
    }
}
