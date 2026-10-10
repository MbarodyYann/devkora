<div>
    {{-- Carte de filtres à cheval sur l'en-tête : compteur, statut, recherche plein texte, tag. --}}
    <x-card padding="p-3 sm:p-5" class="relative z-10 -mt-6 sm:-mt-10">
        <div class="mb-3 flex flex-col gap-3 border-b border-line pb-3 sm:flex-row sm:items-center sm:justify-between">
            @php($total = $this->questions->total())
            <p class="text-sm font-medium text-ink" aria-live="polite">
                <span wire:loading.remove>{{ $total }} {{ $total > 1 ? 'questions' : 'question' }}</span>
                <span wire:loading>Recherche…</span>
            </p>
            <x-segmented label="Statut" model="status" :options="App\Livewire\Questions\Index::STATUSES" />
        </div>
        <form wire:submit.prevent role="search" aria-label="Chercher une question" class="grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] sm:items-end">
            <div class="min-w-0">
                <label for="filtre-q" class="mb-1.5 block text-sm font-medium text-ink">Recherche</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted" />
                    <x-input name="q" id="filtre-q" type="search" wire:model.live.debounce.400ms="q" placeholder="Ex. paiement Wave refusé" class="pl-9" autocomplete="off" />
                </div>
            </div>
            <div class="min-w-0">
                <label for="filtre-tag" class="mb-1.5 block text-sm font-medium text-ink">Tag</label>
                <x-select name="tag" id="filtre-tag" wire:model.live="tag" :options="$this->tagOptions->pluck('name', 'slug')->all()" placeholder="Tous les tags" />
            </div>
        </form>
        @if ($this->hasFilters)
            <div class="mt-3 flex justify-end">
                <x-button variant="ghost" size="sm" wire:click="resetFilters">Effacer les filtres</x-button>
            </div>
        @endif
    </x-card>

    <div wire:loading.class="opacity-60" class="mt-6 transition-opacity">
        @if ($this->questions->isEmpty())
            <x-empty-state title="Personne n'a encore posé cette question.">
                Essaie d'autres mots ou retire un filtre. Si rien ne vient, pose-la : elle aidera tous ceux qui chercheront la même chose.
                <div class="mt-6"><x-button variant="secondary" wire:click="resetFilters">Voir toutes les questions</x-button></div>
            </x-empty-state>
        @else
            <h2 class="sr-only">Résultats</h2>
            <ul class="space-y-4">
                @foreach ($this->questions as $question)
                    <li wire:key="question-{{ $question->id }}"><x-question-card :question="$question" /></li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $this->questions->links() }}</div>
        @endif
    </div>
</div>
