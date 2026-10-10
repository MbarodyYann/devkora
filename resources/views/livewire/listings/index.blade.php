<div>
    {{-- Carte de filtres à cheval sur l'en-tête : compteur, type (segmenté), recherche, compétence, lieu. --}}
    <x-card padding="p-3 sm:p-5" class="relative z-10 -mt-6 sm:-mt-10">
        <div class="mb-3 flex flex-col gap-3 border-b border-line pb-3 sm:flex-row sm:items-center sm:justify-between">
            @php($total = $this->listings->total())
            <p class="text-sm font-medium text-ink" aria-live="polite">
                <span wire:loading.remove>{{ $total }} {{ $total > 1 ? 'annonces' : 'annonce' }}</span>
                <span wire:loading>Recherche…</span>
            </p>
            <x-segmented label="Type d'annonce" model="type" :options="$types" />
        </div>
        <form wire:submit.prevent role="search" aria-label="Chercher une annonce" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)] lg:items-end">
            <div class="min-w-0 sm:col-span-2 lg:col-span-1">
                <label for="filtre-q" class="mb-1.5 block text-sm font-medium text-ink">Recherche</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted" />
                    <x-input name="q" id="filtre-q" type="search" wire:model.live.debounce.400ms="q" placeholder="Ex. module Orange Money" class="pl-9" autocomplete="off" />
                </div>
            </div>
            <div class="min-w-0">
                <label for="filtre-competence" class="mb-1.5 block text-sm font-medium text-ink">Compétence</label>
                <x-select name="competence" id="filtre-competence" wire:model.live="skill" :options="$this->skillOptions->pluck('name', 'slug')->all()" placeholder="Toutes" />
            </div>
            <div class="min-w-0">
                <label for="filtre-lieu" class="mb-1.5 block text-sm font-medium text-ink">Pays ou télétravail</label>
                <x-select name="lieu" id="filtre-lieu" wire:model.live="place" :options="$this->placeOptions" placeholder="Partout" />
            </div>
        </form>
        @if ($this->hasFilters)
            <div class="mt-3 flex justify-end">
                <x-button variant="ghost" size="sm" wire:click="resetFilters">Effacer les filtres</x-button>
            </div>
        @endif
    </x-card>

    {{-- Les événements, absents de la barre du bas sur mobile, sont proposés ici. --}}
    <a href="{{ route('events.index') }}" class="mt-4 flex items-center justify-between gap-3 rounded-2xl border border-line bg-bg-alt px-4 py-3 text-sm font-medium text-ink hover:bg-surface" data-events-link>
        <span class="flex items-center gap-2"><x-icon name="calendar" class="size-4 text-primary-600 dark:text-secondary-400" /> Hackathons, meetups et ateliers : trouve des coéquipiers en vrai</span>
        <x-icon name="arrow-right" class="size-4 shrink-0" />
    </a>

    <div wire:loading.class="opacity-60" class="mt-6 transition-opacity">
        @if ($this->listings->isEmpty())
            <x-empty-state title="Aucune annonce avec ces critères, pour l'instant.">
                Élargis à tous les pays ou à toutes les compétences. Ton projet cherche du monde ? Publie ta propre annonce.
                <div class="mt-6"><x-button variant="secondary" wire:click="resetFilters">Voir toutes les annonces</x-button></div>
            </x-empty-state>
        @else
            <h2 class="sr-only">Résultats</h2>
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->listings as $listing)
                    <li wire:key="listing-{{ $listing->id }}" class="min-w-0"><x-listing-card :listing="$listing" /></li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $this->listings->links() }}</div>
        @endif
    </div>
</div>
