<div>
    <x-card padding="p-3 sm:p-5" class="relative z-10 -mt-6 sm:-mt-10">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0 sm:w-64">
                <label for="filtre-lieu" class="mb-1.5 block text-sm font-medium text-ink">Ville ou en ligne</label>
                <x-select name="lieu" id="filtre-lieu" wire:model.live="place" :options="$this->placeOptions" placeholder="Partout" />
            </div>
            <x-segmented label="Type d'événement" model="type" :options="$types" />
        </div>
    </x-card>

    <section class="mt-8" aria-labelledby="venir-titre" wire:loading.class="opacity-60">
        <h2 id="venir-titre" class="text-[28px] sm:text-[32px]">À venir</h2>
        @if ($this->upcoming->isEmpty())
            <x-empty-state title="Aucun événement à venir ici, pour l'instant." class="mt-4">
                Organise le premier : un meetup de dix personnes suffit pour lancer une communauté locale.
                <div class="mt-6"><x-button variant="secondary" wire:click="resetFilters">Voir tous les événements</x-button></div>
            </x-empty-state>
        @else
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->upcoming as $event)
                    <li wire:key="event-{{ $event->id }}" class="min-w-0"><x-event-card :event="$event" /></li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($this->past->isNotEmpty())
        <section class="mt-12" aria-labelledby="passes-titre">
            <h2 id="passes-titre" class="text-[28px] sm:text-[32px]">Passés</h2>
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->past as $event)
                    <li wire:key="past-{{ $event->id }}" class="min-w-0"><x-event-card :event="$event" /></li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
