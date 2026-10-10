<div>
    {{-- Carte de filtres à cheval sur le bas de l'en-tête compact, compteur dedans (08). --}}
    @php($advanced = collect([$skill, $country, $language])->filter()->count())
    <x-card padding="p-3 sm:p-5" class="relative z-10 -mt-6 sm:-mt-10" x-data="{ open: false }">
        <div class="mb-3 flex flex-col gap-3 border-b border-line pb-3 sm:flex-row sm:items-center sm:justify-between">
            @php($total = $this->devs->total())
            <p class="text-sm font-medium text-ink" aria-live="polite">
                <span wire:loading.remove>{{ $total }} {{ $total > 1 ? 'devs' : 'dev' }}</span>
                <span wire:loading>Recherche…</span>
            </p>
            <x-segmented label="Disponibilité" model="availability" :options="['' => 'Tous'] + $availabilities" />
        </div>

        <form wire:submit.prevent role="search" aria-label="Filtrer les devs" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-1">
                <div class="min-w-0 flex-1">
                    <label for="filtre-q" class="mb-1.5 block text-sm font-medium text-ink max-sm:sr-only">Nom</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted" />
                        <x-input name="q" id="filtre-q" type="search" wire:model.live.debounce.400ms="q" placeholder="Nom du dev" class="pl-9" autocomplete="off" />
                    </div>
                </div>
                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-controls="filtres"
                        class="btn-outline inline-flex min-h-11 shrink-0 items-center gap-2 rounded-field px-3 text-sm font-semibold sm:hidden">
                    <x-icon name="filter" class="size-4" /> Filtres
                    @if ($advanced)
                        <span class="inline-flex size-5 items-center justify-center rounded-full bg-primary-600 text-xs text-white dark:bg-secondary-400 dark:text-primary-950">{{ $advanced }}</span>
                    @endif
                </button>
            </div>
            <div id="filtres" class="contents max-sm:hidden" x-bind:class="open && 'max-sm:grid! max-sm:gap-3'">
                <div class="min-w-0">
                    <label for="filtre-competence" class="mb-1.5 block text-sm font-medium text-ink">Compétence</label>
                    <x-select name="competence" id="filtre-competence" wire:model.live="skill" :options="$this->skillOptions->pluck('name', 'slug')->all()" placeholder="Toutes" />
                </div>
                <div class="min-w-0">
                    <label for="filtre-pays" class="mb-1.5 block text-sm font-medium text-ink">Pays</label>
                    <x-select name="pays" id="filtre-pays" wire:model.live="country" :options="$this->countryOptions" placeholder="Tous les pays" />
                </div>
                <div class="min-w-0">
                    <label for="filtre-langue" class="mb-1.5 block text-sm font-medium text-ink">Langue</label>
                    <x-select name="langue" id="filtre-langue" wire:model.live="language" :options="$this->languageOptions" placeholder="Toutes" />
                </div>
            </div>
        </form>

        @if ($this->hasFilters)
            <div class="mt-3 flex justify-end">
                <x-button variant="ghost" size="sm" wire:click="resetFilters">Effacer les filtres</x-button>
            </div>
        @endif
    </x-card>

    @if ($this->contributors->isNotEmpty())
        <section class="mt-8" aria-labelledby="utiles-titre">
            <h2 id="utiles-titre" class="text-[28px] sm:text-[32px]">Les plus utiles ce mois-ci</h2>
            <x-contributors :contributors="$this->contributors" :compact="true" class="mt-4" />
        </section>
    @endif

    <div wire:loading.class="opacity-60" class="mt-6 transition-opacity">
        @if ($this->devs->isEmpty())
            <x-empty-state title="Personne avec ces critères, pour l'instant.">
                Élargis à tous les pays ou à toutes les compétences : le bon dev est peut-être juste à côté.
                <div class="mt-6">
                    <x-button variant="secondary" wire:click="resetFilters">Voir tous les devs</x-button>
                </div>
            </x-empty-state>
        @else
            <h2 class="sr-only">Résultats</h2>
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->devs as $dev)
                    <li wire:key="dev-{{ $dev->id }}" class="min-w-0"><x-dev-card :dev="$dev" /></li>
                @endforeach
            </ul>

            <div class="mt-8">
                {{ $this->devs->links() }}
            </div>
        @endif
    </div>
</div>
