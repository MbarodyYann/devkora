@php
    use App\Support\Brand;
    use App\Support\Money;
@endphp

{{-- Accueil : le fil des questions. Le visiteur cherche, lit et parcourt sans compte ; un seul bouton
     « Pose ta question », dans l'en-tête. La colonne latérale passe sous le fil en dessous de 1 024 px. --}}
<x-layouts.app :description="Brand::TAGLINE" welcome :page-hero="[
    'start' => 'Ce que les devs se demandent',
    'end' => 'en ce moment.',
    'subtitle' => 'Cherche, lis et réponds sans attendre : la communauté vote, la meilleure réponse reste trouvable par tous.',
    'action' => ['label' => 'Pose ta question', 'route' => 'questions.create', 'guests' => true],
]">
    <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start lg:gap-8" data-feed>
        <div class="min-w-0">
            <livewire:questions.index />
        </div>

        <aside aria-label="Autour du fil" data-feed-aside class="relative z-10 mt-12 min-w-0 space-y-6 lg:-mt-10">
            @if ($localTags->isNotEmpty())
                <x-card padding="p-5" aria-labelledby="tags-titre">
                    <h2 id="tags-titre" class="font-serif text-xl">Les sujets d'ici</h2>
                    <x-tags :skills="$localTags" class="mt-4" />
                </x-card>
            @endif

            @if ($upcomingEvents->isNotEmpty())
                <x-card padding="p-5" aria-labelledby="evenements-titre">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 id="evenements-titre" class="font-serif text-xl">Prochains événements</h2>
                        <a href="{{ route('events.index') }}" class="shrink-0 text-sm font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">Tout voir<span class="sr-only"> : les événements</span></a>
                    </div>
                    <ul class="mt-2 divide-y divide-line">
                        @foreach ($upcomingEvents as $event)
                            <li class="py-3 last:pb-0">
                                <a href="{{ route('events.show', $event) }}" class="block font-medium leading-snug text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $event->title }}</a>
                                <p class="mt-1 text-sm text-ink-muted">{{ $event->whenLabel() }} · {{ $event->placeLabel() }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($openListings->isNotEmpty())
                <x-card padding="p-5" aria-labelledby="annonces-titre">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 id="annonces-titre" class="font-serif text-xl">Des devs cherchent du renfort</h2>
                        <a href="{{ route('listings.index') }}" class="shrink-0 text-sm font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">Tout voir<span class="sr-only"> : les annonces</span></a>
                    </div>
                    <ul class="mt-2 divide-y divide-line">
                        @foreach ($openListings as $listing)
                            <li class="py-3 last:pb-0">
                                <a href="{{ route('listings.show', $listing) }}" class="block font-medium leading-snug text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $listing->title }}</a>
                                <p class="mt-1 text-sm text-ink-muted">
                                    {{ $listing->type->label() }} ·
                                    @if ($listing->isMission())
                                        {{ Money::fcfa($listing->budget_fcfa) }}
                                    @else
                                        {{ $listing->placeLabel() }}
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($contributors->isNotEmpty())
                <section aria-labelledby="contributeurs-titre">
                    <h2 id="contributeurs-titre" class="font-serif text-xl">Les plus utiles ce mois-ci</h2>
                    <x-contributors :contributors="$contributors" compact stacked class="mt-4" />
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
