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
        <div class="min-w-0 space-y-6">
        {{-- Ce qu'il partage (02) : réponses acceptées et questions, avant le reste --}}
        <x-card data-shares>
            <h2 class="text-[28px] sm:text-[32px]">{{ $isOwner ? 'Ce que tu partages' : 'Ce que '.$dev->firstName().' partage' }}</h2>
            @if ($articles->isEmpty() && $acceptedAnswers->isEmpty() && $questions->isEmpty())
                <p class="mt-4 text-ink-muted">
                    {{ $isOwner ? 'Tes articles, tes réponses acceptées et tes questions apparaîtront ici. Une réponse acceptée te rapporte +15 cauris.' : $dev->firstName().' n\'a encore rien partagé.' }}
                </p>
            @endif
            @if ($articles->isNotEmpty())
                <h3 class="mt-6 font-sans text-base font-semibold tracking-normal text-ink">Articles <span class="font-normal text-ink-muted">({{ $articlesCount }})</span></h3>
                <ul class="mt-2 divide-y divide-line">
                    @foreach ($articles as $article)
                        <li class="flex items-start gap-3 py-3">
                            <a href="{{ route('articles.show', $article) }}" class="min-w-0 flex-1 break-words font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $article->title }}</a>
                            <span class="shrink-0 text-sm text-ink-muted">{{ $article->score }} × Utile</span>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($acceptedAnswers->isNotEmpty())
                <h3 class="mt-6 font-sans text-base font-semibold tracking-normal text-ink">Réponses acceptées <span class="font-normal text-ink-muted">({{ $acceptedCount }})</span></h3>
                <ul class="mt-2 divide-y divide-line">
                    @foreach ($acceptedAnswers as $answer)
                        <li class="flex items-start gap-3 py-3">
                            <x-chip tone="done" class="shrink-0 py-0.5! text-xs!">Acceptée</x-chip>
                            <a href="{{ route('questions.show', $answer->question).'#reponse-'.$answer->id }}" class="min-w-0 flex-1 break-words font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $answer->question->title }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($questions->isNotEmpty())
                <h3 class="mt-6 font-sans text-base font-semibold tracking-normal text-ink">Questions <span class="font-normal text-ink-muted">({{ $questionsCount }})</span></h3>
                <ul class="mt-2 divide-y divide-line">
                    @foreach ($questions as $question)
                        <li class="flex items-start gap-3 py-3">
                            <a href="{{ route('questions.show', $question) }}" class="min-w-0 flex-1 break-words font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $question->title }}</a>
                            <span class="shrink-0 text-sm text-ink-muted">{{ $question->answers_count }} {{ $question->answers_count > 1 ? 'réponses' : 'réponse' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        {{-- Projets et équipes (02) --}}
        @if ($teams->isNotEmpty() || $isOwner)
            <x-card data-teams>
                <h2 class="text-[28px] sm:text-[32px]">Projets et équipes</h2>
                @if ($teams->isEmpty())
                    <p class="mt-4 text-ink-muted">Les équipes que tu montes ou que tu rejoins apparaîtront ici. Les annonces « Cherche coéquipiers » t'attendent.</p>
                @else
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($teams as $team)
                            <li class="flex items-center gap-3 py-3">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('listings.show', $team) }}" class="block break-words font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $team->title }}</a>
                                    <p class="text-sm text-ink-muted">{{ $team->user_id === $dev->id ? 'Porteur du projet' : 'Membre de l\'équipe' }} · {{ $team->acceptedProposals->count() + 1 }} dans l'équipe</p>
                                </div>
                                <span class="flex shrink-0 -space-x-1.5" aria-hidden="true">
                                    <x-avatar :user="$team->user" size="stack" class="font-sans! font-semibold!" />
                                    @foreach ($team->acceptedProposals->take(3) as $member)
                                        <x-avatar :user="$member->user" size="stack" class="font-sans! font-semibold!" />
                                    @endforeach
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        @endif

        {{-- Missions réalisées et avis (02) --}}
        @if ($completedMissions->isNotEmpty() || $isOwner)
            <x-card id="missions" data-missions>
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 class="text-[28px] sm:text-[32px]">Missions réalisées</h2>
                    @if ($reviewStats?->count)
                        <p class="flex items-center gap-2 text-sm text-ink-muted"><x-stars :value="(float) $reviewStats->average" /> {{ number_format((float) $reviewStats->average, 1, ',', '') }} · {{ $reviewStats->count }} avis</p>
                    @endif
                </div>
                @if ($completedMissions->isEmpty())
                    <p class="mt-4 text-ink-muted">Ta première mission terminée apparaîtra ici, avec +20 cauris et l'avis de l'auteur.</p>
                @else
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($completedMissions as $done)
                            <li class="py-4">
                                @if ($done->review)
                                    <x-review-detail :review="$done->review" :mission="$done" />
                                @else
                                    <a href="{{ route('listings.show', $done) }}" class="font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $done->title }}</a>
                                    <p class="mt-1 text-sm text-ink-muted">Pour {{ $done->user->name }} · terminée le {{ $done->completed_at->translatedFormat('j M Y') }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        @endif

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
        </div>

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
