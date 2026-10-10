@props(['event'])

{{-- Carte événement : type, date, titre, lieu ou en ligne, participants. --}}
<article {{ $attributes->merge(['class' => 'relative flex h-full min-w-0 flex-col rounded-2xl border border-line bg-surface p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:hover:translate-y-0 sm:rounded-card sm:p-6']) }}>
    <div class="flex flex-wrap items-center gap-2">
        <x-chip :tone="$event->type === App\Enums\EventType::Hackathon ? 'warm' : 'info'">{{ $event->type->label() }}</x-chip>
        @if ($event->isPast())
            <x-chip tone="muted">Passé</x-chip>
        @endif
    </div>
    <p class="mt-4 flex items-center gap-1.5 text-sm font-semibold text-primary-600 dark:text-primary-300"><x-icon name="calendar" class="size-4" /> {{ $event->whenLabel() }}</p>
    <h3 class="mt-2 font-serif text-[22px] leading-snug">
        <a href="{{ route('events.show', $event) }}" class="text-ink after:absolute after:inset-0 hover:text-primary-600 dark:hover:text-primary-300">{{ $event->title }}</a>
    </h3>
    <p class="mt-3 line-clamp-2 break-words text-ink-soft">{{ $event->excerpt }}</p>
    <div class="mt-auto pt-5">
    <p class="flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-line pt-4 text-sm text-ink-muted">
        @if ($event->online)
            <span class="inline-flex items-center gap-1"><x-icon name="map-pin" class="size-4" /> En ligne</span>
        @else
            <a href="{{ route('cities.show', App\Support\Cities::slug($event->city)) }}" class="relative z-10 inline-flex items-center gap-1 hover:text-ink hover:underline"><x-icon name="map-pin" class="size-4" /> {{ $event->placeLabel() }}</a>
        @endif
        <span class="ml-auto inline-flex items-center gap-2">
            @if ($event->relationLoaded('participants') && $event->participants->isNotEmpty())
                <span class="flex -space-x-1.5" aria-hidden="true">
                    @foreach ($event->participants->take(4) as $participant)
                        <x-avatar :user="$participant" size="stack" class="font-sans! font-semibold!" />
                    @endforeach
                </span>
            @endif
            {{ $event->participants_count }} {{ $event->participants_count > 1 ? 'participants' : 'participant' }}
        </span>
    </p>
    </div>
</article>
