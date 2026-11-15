@props(['question', 'live' => false])

{{-- Carte question (08) : score, réponses (coche si acceptée), titre en Newsreader 20 px, extrait de 2 lignes,
     tags, auteur et date. live (accueil, questions du moment) : étiquette « Résolue » aussi sur ordinateur,
     date relative (« il y a 3 h ») et pays de l'auteur, pour montrer que le site est actif. --}}
<article {{ $attributes->merge(['class' => 'relative flex min-w-0 gap-4 rounded-2xl border border-line bg-surface p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:hover:translate-y-0 sm:rounded-card sm:p-6']) }}>
    <dl class="hidden w-20 shrink-0 flex-col items-end gap-2 pt-1 text-sm sm:flex">
        <div class="flex items-baseline gap-1 text-ink-muted"><dd class="font-semibold tabular-nums text-ink">{{ $question->score }}</dd><dt>{{ abs($question->score) > 1 ? 'votes' : 'vote' }}</dt></div>
        <div @class([
            'flex items-baseline gap-1 rounded-[6px] px-2 py-0.5',
            'bg-primary-600 text-white dark:bg-secondary-400 dark:text-primary-950' => $question->isResolved(),
            'text-ink-muted' => ! $question->isResolved(),
        ])>
            @if ($question->isResolved())
                <x-icon name="check" class="size-3.5 self-center" />
            @endif
            <dd class="font-semibold tabular-nums">{{ $question->answers_count }}</dd><dt>{{ $question->answers_count > 1 ? 'réponses' : 'réponse' }}</dt>
        </div>
        @if ($live && $question->isResolved())
            <div><dt class="sr-only">Statut</dt><dd><x-chip tone="done" class="py-0.5! text-xs!">Résolue</x-chip></dd></div>
        @endif
    </dl>
    <div class="min-w-0 flex-1">
        <h3 class="font-serif text-xl leading-snug">
            <a href="{{ route('questions.show', $question) }}" class="text-ink after:absolute after:inset-0 hover:text-primary-600 dark:hover:text-primary-300">{{ $question->title }}</a>
        </h3>
        <p class="mt-2 line-clamp-2 break-words text-ink-soft">{{ $question->excerpt }}</p>
        {{-- Mobile : score et réponses sur une ligne --}}
        <p class="mt-3 flex items-center gap-3 text-sm text-ink-muted sm:hidden">
            <span>{{ $question->score }} {{ abs($question->score) > 1 ? 'votes' : 'vote' }}</span>
            @if ($question->isResolved())
                <x-chip tone="done" class="py-0.5! text-xs!">Résolue</x-chip>
            @endif
            <span>{{ $question->answers_count }} {{ $question->answers_count > 1 ? 'réponses' : 'réponse' }}</span>
        </p>
        <div class="relative z-10 mt-4 flex flex-wrap items-center justify-between gap-3">
            <x-tags :skills="$question->skills" />
            <x-author :user="$question->user" :date="$question->created_at" :relative="$live" :country="$live" class="ml-auto" />
        </div>
    </div>
</article>
