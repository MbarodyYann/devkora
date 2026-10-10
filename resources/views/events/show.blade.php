@php($past = $event->isPast())

<x-layouts.app :title="$event->title" :description="$event->excerpt">
    <a href="{{ route('events.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-left" class="size-4" /> Tous les événements
    </a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap gap-2">
                        <x-chip :tone="$event->type === App\Enums\EventType::Hackathon ? 'warm' : 'info'">{{ $event->type->label() }}</x-chip>
                        @if ($past)<x-chip tone="muted">Passé</x-chip>@endif
                    </div>
                    <x-report-button :content="$event" type="event" />
                </div>
                <h1 class="mt-4 break-words text-[30px] leading-[1.15] sm:text-[40px]">{{ $event->title }}</h1>
                <dl class="mt-5 space-y-2 text-ink">
                    <div class="flex items-start gap-2"><dt class="sr-only">Date</dt><x-icon name="calendar" class="mt-0.5 size-5 shrink-0 text-ink-muted" /><dd>{{ $event->whenLabel() }}{{ $event->ends_at->isSameDay($event->starts_at) ? ' à '.str_replace(' h 00', ' h', $event->ends_at->format('G \h i')) : '' }}</dd></div>
                    <div class="flex items-start gap-2"><dt class="sr-only">Lieu</dt><x-icon name="map-pin" class="mt-0.5 size-5 shrink-0 text-ink-muted" /><dd>{{ $event->placeLabel() }}</dd></div>
                </dl>
                <div class="mt-4"><x-author :user="$event->user" :date="$event->created_at" verb="Proposé" /></div>
                <div class="prose-dk mt-6 border-t border-line pt-6">{!! $event->description_html !!}</div>
            </x-card>

            @if ($event->type === App\Enums\EventType::Hackathon)
                <x-card id="equipes" data-teams>
                    <h2 class="text-[28px] sm:text-[32px]">Les équipes qui se forment</h2>
                    @if ($teams->isEmpty())
                        <p class="mt-3 text-ink-muted">Aucune équipe pour l'instant. Lance la tienne : les devs intéressés t'écriront.</p>
                    @else
                        <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($teams as $team)
                                <li class="min-w-0"><x-listing-card :listing="$team" /></li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! $past)
                        @auth
                            <x-button :href="route('listings.create', ['type' => 'team', 'evenement' => $event->id])" variant="secondary" class="mt-5 w-full whitespace-normal! py-2 text-center sm:w-auto">Chercher des coéquipiers pour cet événement</x-button>
                        @endauth
                    @endif
                </x-card>
            @endif
        </div>

        <aside class="min-w-0 space-y-6" aria-label="Participer">
            <x-card padding="p-6" data-participation>
                <p class="font-serif text-[28px] leading-none text-ink">{{ $event->participants_count }} <span class="font-sans text-base text-ink-muted">{{ $event->participants_count > 1 ? 'participants' : 'participant' }}</span></p>
                @if ($participants->isNotEmpty())
                    <ul class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($participants as $participant)
                            <li><a href="{{ route('devs.show', $participant) }}" title="{{ $participant->name }}"><x-avatar :user="$participant" size="xs" /><span class="sr-only">{{ $participant->name }}</span></a></li>
                        @endforeach
                    </ul>
                @endif

                @if ($past)
                    <p class="mt-5 text-ink-muted">Cet événement est terminé.</p>
                @else
                    @auth
                        <form method="post" action="{{ route('events.participate', $event) }}" class="mt-5">
                            @csrf
                            <x-button type="submit" :variant="$participating ? 'secondary' : 'primary'" class="w-full">{{ $participating ? 'Je n\'y vais plus' : 'J\'y participe' }}</x-button>
                        </form>
                        <p class="mt-3 text-center text-sm text-ink-muted">{{ $participating ? 'Tu fais partie des participants.' : 'Les autres participants te voient, et tu les vois.' }}</p>
                    @else
                        <x-button :href="route('register')" class="mt-5 w-full">{{ App\Support\Copy::SIGNUP_CTA }}</x-button>
                        <p class="mt-3 text-center text-sm text-ink-muted">{{ App\Support\Copy::SIGNUP_REASSURANCE }}</p>
                    @endauth
                    @if ($event->registration_url)
                        <a href="{{ $event->registration_url }}" rel="nofollow noopener noreferrer" target="_blank" class="mt-4 flex items-center justify-center gap-1.5 text-sm font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">
                            Inscription chez l'organisateur <x-icon name="arrow-right" class="size-4" />
                        </a>
                    @endif
                @endif
            </x-card>
        </aside>
    </div>
</x-layouts.app>
