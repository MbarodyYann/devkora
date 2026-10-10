@props(['start', 'end', 'subtitle', 'action' => null])

{{-- En-tête compact des pages de liste (08) : bandeau primary-800 (primary-950 en sombre) avec arcs, sans photo ;
     H1 dont la fin est en italique ambre ; sous-titre masqué sur mobile. Visiteur : aucun CTA (celui de la
     navigation suffit). Membre : l'action de la page, à droite. Le bas est libre pour la carte de filtres. --}}
<section id="hero" aria-labelledby="page-titre" class="relative -mt-[68px] overflow-hidden bg-primary-800 pt-[68px] text-white sm:-mt-[76px] sm:pt-[76px] dark:bg-primary-950">
    <svg class="pointer-events-none absolute bottom-0 left-0 h-[420px] w-[420px]" viewBox="0 0 760 760" fill="none" aria-hidden="true">
        @foreach ([340, 500, 660] as $radius)
            <circle cx="0" cy="760" r="{{ $radius }}" stroke="white" stroke-opacity=".06" stroke-width="1" />
        @endforeach
    </svg>

    {{-- Respiration : 32 px sous la navigation et 40 px avant la carte de filtres sur mobile ;
         60 px et 72 px sur ordinateur. --}}
    <div @class([
        'relative mx-auto flex max-w-page flex-col gap-5 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:gap-10',
        'pt-8 pb-16 sm:pt-[60px] sm:pb-28' => auth()->guest(),
        'pt-6 pb-12 sm:pt-10 sm:pb-24' => auth()->check(),
    ])>
        <div class="min-w-0">
            <h1 id="page-titre" class="text-balance text-[30px] leading-[1.1] text-white sm:text-[40px]">
                {{ $start }} <em class="font-serif italic text-secondary-400">{{ $end }}</em>
            </h1>
            <p class="mt-4 hidden max-w-2xl leading-[1.6] text-white/80 sm:block">{{ $subtitle }}</p>
        </div>

        @auth
            @if ($action)
                <x-button :href="route($action['route'])" class="shrink-0 self-start lg:self-center"><x-icon name="plus" class="size-4" /> {{ $action['label'] }}</x-button>
            @endif
        @endauth
    </div>
</section>
