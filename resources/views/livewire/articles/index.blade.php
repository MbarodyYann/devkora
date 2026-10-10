<div>
    {{-- Carte de filtres à cheval sur l'en-tête : compteur, recherche, catégorie, tag. --}}
    <x-card padding="p-3 sm:p-5" class="relative z-10 -mt-6 sm:-mt-10">
        @php($total = $this->articles->total())
        <p class="mb-3 border-b border-line pb-3 text-sm font-medium text-ink" aria-live="polite">
            <span wire:loading.remove>{{ $total }} {{ $total > 1 ? 'articles' : 'article' }}</span>
            <span wire:loading>Recherche…</span>
        </p>
        <form wire:submit.prevent role="search" aria-label="Chercher un article" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)] lg:items-end">
            <div class="min-w-0 sm:col-span-2 lg:col-span-1">
                <label for="filtre-q" class="mb-1.5 block text-sm font-medium text-ink">Recherche</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-muted" />
                    <x-input name="q" id="filtre-q" type="search" wire:model.live.debounce.400ms="q" placeholder="Ex. deploiement VPS" class="pl-9" autocomplete="off" />
                </div>
            </div>
            <div class="min-w-0">
                <label for="filtre-categorie" class="mb-1.5 block text-sm font-medium text-ink">Catégorie</label>
                <x-select name="categorie" id="filtre-categorie" wire:model.live="category" :options="$categories" placeholder="Toutes" />
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
        @if ($this->articles->isEmpty())
            <x-empty-state title="Aucun article sur ce sujet, pour l'instant.">
                Essaie d'autres mots ou retire un filtre. Tu maîtrises ce sujet ? Ton retour d'expérience aidera d'autres devs.
                <div class="mt-6"><x-button variant="secondary" wire:click="resetFilters">Voir tous les articles</x-button></div>
            </x-empty-state>
        @else
            <h2 class="sr-only">Résultats</h2>
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->articles as $article)
                    <li wire:key="article-{{ $article->id }}" class="min-w-0"><x-article-card :article="$article" /></li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $this->articles->links() }}</div>
        @endif
    </div>
</div>
