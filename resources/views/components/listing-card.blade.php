@props(['listing'])

{{-- Carte annonce (08) : type, statut, titre en Newsreader 22 px, extrait ; mission : budget en Newsreader 28 px ;
     équipe : rôles recherchés et avatars des membres acceptés ; pays ou télétravail ; nombre de réponses. --}}
@php($team = ! $listing->isMission())
<article {{ $attributes->merge(['class' => 'relative flex h-full min-w-0 flex-col rounded-2xl border border-line bg-surface p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:hover:translate-y-0 sm:rounded-card sm:p-6']) }}>
    <div class="flex flex-wrap items-center gap-2">
        <x-chip :tone="$team ? 'neutral' : 'info'">{{ $listing->type->label() }}</x-chip>
        @unless ($listing->isOpen())
            <x-chip :tone="$listing->status->tone()">{{ $listing->status->label($listing->type) }}</x-chip>
        @endunless
    </div>
    <h3 class="mt-4 font-serif text-[22px] leading-snug">
        <a href="{{ route('listings.show', $listing) }}" class="text-ink after:absolute after:inset-0 hover:text-primary-600 dark:hover:text-primary-300">{{ $listing->title }}</a>
    </h3>
    <p class="mt-3 line-clamp-2 break-words text-ink-soft">{{ $listing->excerpt }}</p>

    <div class="mt-auto pt-5">
        @if ($team)
            <p class="text-sm text-ink-muted"><span class="font-medium text-ink">Rôles :</span> {{ $listing->roles_wanted }}</p>
            @if ($listing->acceptedProposals->isNotEmpty())
                <div class="mt-3 flex items-center gap-2">
                    <span class="flex -space-x-1.5">
                        <x-avatar :user="$listing->user" size="stack" class="font-sans! font-semibold!" />
                        @foreach ($listing->acceptedProposals->take(4) as $member)
                            <x-avatar :user="$member->user" size="stack" class="font-sans! font-semibold!" />
                        @endforeach
                    </span>
                    <span class="text-sm text-ink-muted">{{ $listing->acceptedProposals->count() + 1 }} dans l'équipe</span>
                </div>
            @endif
        @else
            <x-budget :amount="$listing->budget_fcfa" />
        @endif
        <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-line pt-4 text-sm text-ink-muted">
            <span class="inline-flex items-center gap-1"><x-icon name="map-pin" class="size-4" /> {{ $listing->placeLabel() }}</span>
            <span>{{ $listing->proposals_count }} {{ $listing->proposals_count > 1 ? 'réponses' : 'réponse' }}</span>
            <span class="ml-auto inline-flex min-w-0 items-center gap-1.5">
                <x-avatar :user="$listing->user" size="xs" />
                <span class="truncate font-medium text-ink">{{ $listing->user->firstName() }}</span>
            </span>
        </p>
    </div>
</article>
