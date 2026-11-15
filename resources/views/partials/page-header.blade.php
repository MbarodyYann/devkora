{{-- En-tête d'une page : le titre dit ce que la page contient, l'action principale est sur la même ligne.
     Une action réservée aux membres ne s'affiche pas pour le visiteur, sauf si elle est marquée « guests ». --}}
@php
    $action ??= null;
    $subtitle ??= null;
    $showAction = $action && (auth()->check() || ($action['guests'] ?? false));
@endphp
<header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3" data-page-header>
    <div class="min-w-0">
        <h1 id="page-titre" class="text-[28px] leading-tight sm:text-[36px]">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 max-w-2xl text-ink-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($showAction)
        <x-button :href="route($action['route'])" class="shrink-0" data-cta="question"><x-icon name="plus" class="size-4" /> {{ $action['label'] }}</x-button>
    @endif
</header>
