@php
    $profile = $dev->profile;
    $isOwner = auth()->id() === $dev->id;
    // Encart du propriétaire : bienvenue juste après l'inscription, ou profil sans compétences (11, « Profil »).
    $showPrompt = $isOwner && (session('welcome') || $dev->skills->isEmpty());
@endphp

<x-layouts.app :title="$dev->name" :description="collect([$profile->headline, $profile->city.', '.$profile->countryName(), $profile->bio])->filter()->join('. ')">
    @if ($showPrompt)
        <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-secondary-400 bg-secondary-50 p-5 sm:flex-row sm:items-center sm:justify-between dark:bg-secondary-950" role="status" data-profile-prompt>
            <p class="text-ink">
                @if (session('welcome'))
                    <strong class="font-semibold">Bienvenue !</strong> Ajoute tes compétences pour que les autres devs te trouvent.
                @else
                    Ajoute tes compétences pour apparaître dans les recherches des autres devs.
                @endif
            </p>
            <x-button :href="route('profile.edit', $dev)" class="shrink-0">Compléter mon profil</x-button>
        </div>
    @endif

    {{-- En-tête : identité à gauche, compteur de cauris dans le cadre repères à droite --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <x-card class="flex flex-col gap-6 sm:flex-row sm:items-start">
            <x-avatar :user="$dev" size="lg" />
            <div class="min-w-0 flex-1">
                <h1 class="break-words text-[38px] sm:text-5xl">{{ $dev->name }}</h1>
                @if ($profile->headline)
                    <p class="mt-3 text-lg text-ink-soft">{{ $profile->headline }}</p>
                @endif
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <x-chip :tone="$profile->availability->tone()">{{ $profile->availability->label() }}</x-chip>
                    <x-chip tone="neutral"><x-icon name="map-pin" class="size-3.5 shrink-0" /> {{ $profile->city }}, {{ $profile->countryName() }} <span aria-hidden="true">{{ $profile->flag() }}</span></x-chip>
                </div>
                @if ($profile->languagesLabel() || $dev->activityLabel())
                    <p class="mt-3 text-sm text-ink-muted">{{ collect([$profile->languagesLabel(), $dev->activityLabel()])->filter()->join(' · ') }}</p>
                @endif
                @if ($isOwner && ! $showPrompt)
                    <x-button :href="route('profile.edit', $dev)" variant="secondary" size="sm" class="mt-5" data-edit-profile="true">
                        <x-icon name="pencil" class="size-4" /> Modifier mon profil
                    </x-button>
                @endif
            </div>
        </x-card>

        <x-frame class="flex min-w-0 flex-col justify-between gap-6 bg-surface p-6 sm:p-8" data-cauris>
            <div>
                @if ($dev->reputation > 0)
                    <p class="flex items-center gap-3">
                        <x-cauri class="size-10 text-secondary-400" />
                        <span class="font-serif text-[56px] font-medium leading-none tracking-tight text-ink">{{ number_format($dev->reputation, 0, ',', "\u{202F}") }}</span>
                    </p>
                    <p class="mt-2 text-sm font-medium text-ink-muted">cauris gagnés en aidant la communauté</p>
                @else
                    <x-cauri class="size-10 text-secondary-400" />
                    <p class="mt-3 text-ink-soft">Pas encore de cauris : chaque réponse utile, chaque article et chaque mission en rapportent.</p>
                @endif
            </div>
            <dl class="grid grid-cols-2 divide-x divide-line border-t border-line pt-4 text-sm">
                <div class="min-w-0 pr-4">
                    <dt class="text-ink-muted">Niveau</dt>
                    <dd class="mt-1 font-serif text-2xl font-medium text-ink">{{ $dev->level()->label() }}</dd>
                </div>
                <div class="min-w-0 pl-4">
                    <dt class="text-ink-muted">Membre depuis</dt>
                    <dd class="mt-1 whitespace-nowrap font-serif text-2xl font-medium text-ink">{{ $dev->created_at->translatedFormat('M Y') }}</dd>
                </div>
            </dl>
        </x-frame>
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <x-card>
            <h2 class="text-[28px] sm:text-[32px]">À propos</h2>
            @if ($profile->bio)
                <p class="mt-4 max-w-prose whitespace-pre-line break-words leading-relaxed">{{ $profile->bio }}</p>
            @elseif ($isOwner)
                <p class="mt-4 text-ink-muted">Quelques lignes sur ce que tu construis et ce que tu sais faire aident les autres devs à te contacter.</p>
            @else
                <p class="mt-4 text-ink-muted">{{ $dev->firstName() }} n'a pas encore écrit de présentation.</p>
            @endif
        </x-card>

        <aside class="min-w-0 space-y-6" aria-label="Compétences et liens">
            <x-card padding="p-6">
                <h2 class="text-2xl">Compétences</h2>
                @if ($dev->skills->isNotEmpty())
                    <x-skills :skills="$dev->skills" class="mt-4" />
                @else
                    <p class="mt-4 text-ink-muted">Pas encore renseignées.</p>
                @endif
            </x-card>

            @if ($profile->github_url || $profile->portfolio_url)
                <x-card padding="p-6">
                    <h2 class="text-2xl">Liens</h2>
                    <ul class="mt-3 divide-y divide-line">
                        @foreach (array_filter(['GitHub' => [$profile->github_url, 'github'], 'Portfolio' => [$profile->portfolio_url, 'link']], fn ($link) => $link[0]) as $label => [$url, $icon])
                            <li>
                                <a href="{{ $url }}" rel="nofollow ugc noopener noreferrer" target="_blank"
                                   class="flex min-h-11 items-center gap-2 font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">
                                    <x-icon :name="$icon" class="size-5" /> {{ $label }} <x-icon name="arrow-right" class="ml-auto size-4 text-ink-muted" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </aside>
    </div>
</x-layouts.app>
