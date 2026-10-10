@props(['dev'])

{{-- Carte d'un dev (annuaire, 08 « Carte dev ») : avatar, nom, titre, ville et drapeau, langues,
     3 compétences, cauris et niveau, disponibilité. Les compétences sont calées en bas pour aligner
     les cartes d'une rangée. --}}
@php($profile = $dev->profile)

<x-card :href="route('devs.show', $dev)" padding="p-5 sm:p-6" {{ $attributes->merge(['class' => 'flex h-full flex-col gap-4']) }}>
    <div class="flex min-w-0 items-start gap-3">
        <x-avatar :user="$dev" />
        <div class="min-w-0 flex-1">
            <p class="truncate font-serif text-xl font-medium leading-tight text-ink">{{ $dev->name }}</p>
            @if ($profile->headline)
                <p class="mt-0.5 truncate text-sm text-ink-muted">{{ $profile->headline }}</p>
            @endif
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                <x-reputation :points="$dev->reputation" />
                <x-level :user="$dev" />
            </div>
        </div>
    </div>

    <div class="space-y-2 text-sm text-ink-muted">
        <p class="flex min-w-0 items-center gap-1.5">
            <x-icon name="map-pin" class="size-4 shrink-0" />
            <span class="truncate">{{ $profile->city }}, {{ $profile->countryName() }}</span>
            <span class="shrink-0" aria-hidden="true">{{ $profile->flag() }}</span>
        </p>
        @if ($languages = $profile->languageNames())
            <p class="flex min-w-0 items-center gap-1.5">
                <x-icon name="languages" class="size-4 shrink-0" />
                <span class="sr-only">Parle </span><span class="truncate">{{ Str::ucfirst(implode(', ', $languages)) }}</span>
            </p>
        @endif
        <x-chip :tone="$profile->availability->tone()">{{ $profile->availability->label() }}</x-chip>
    </div>

    <div class="mt-auto border-t border-line pt-4">
        @if ($dev->skills->isNotEmpty())
            <x-skills :skills="$dev->skills" :limit="3" :link="false" />
        @else
            <p class="text-sm text-ink-muted">Compétences pas encore renseignées</p>
        @endif
    </div>
</x-card>
