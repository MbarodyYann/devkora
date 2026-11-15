@props(['start', 'end', 'subtitle', 'action' => null])

{{-- En-tête d'une page de liste : le titre de la page et une phrase, sur le fond de la page, sans bandeau de couleur.
     Membre : l'action de la page, à droite. Une action marquée « guests » (« Pose ta question » sur l'accueil)
     s'affiche aussi pour le visiteur, qui passe par la connexion. Le bas laisse la place à la carte de filtres. --}}
@php($showAction = $action && (auth()->check() || ($action['guests'] ?? false)))
<section id="hero" aria-labelledby="page-titre">
    <div class="mx-auto flex max-w-page flex-col gap-4 px-4 pb-12 pt-8 sm:px-6 sm:pb-[72px] sm:pt-12 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
        <div class="min-w-0">
            <h1 id="page-titre">{{ $start }} {{ $end }}</h1>
            <p class="mt-3 hidden max-w-2xl leading-relaxed text-ink-soft sm:block">{{ $subtitle }}</p>
        </div>

        @if ($showAction)
            <x-button :href="route($action['route'])" class="shrink-0 self-start lg:self-end" data-cta="question"><x-icon name="plus" class="size-4" /> {{ $action['label'] }}</x-button>
        @endif
    </div>
</section>
