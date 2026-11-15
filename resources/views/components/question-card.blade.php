@props(['question', 'live' => false])

{{-- Carte question : titre, extrait de 2 lignes, puis une ligne de repères (votes, réponses, « Résolue »),
     les tags, l'auteur et la date. Les repères sont dans la carte, jamais collés à son bord.
     live : date relative (« il y a 3 h ») et pays de l'auteur. --}}
<article {{ $attributes->merge(['class' => 'relative min-w-0 rounded-2xl border border-line bg-surface p-4 transition hover:border-ink-muted sm:p-6']) }}>
    <h3 class="text-lg leading-snug sm:text-xl">
        <a href="{{ route('questions.show', $question) }}" class="text-ink after:absolute after:inset-0 hover:text-primary-600 dark:hover:text-primary-300">{{ $question->title }}</a>
    </h3>
    <p class="mt-2 line-clamp-2 break-words text-ink-soft">{{ $question->excerpt }}</p>
    <dl class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-muted">
        <div class="flex items-baseline gap-1"><dd class="font-medium tabular-nums text-ink">{{ $question->score }}</dd><dt>{{ abs($question->score) > 1 ? 'votes' : 'vote' }}</dt></div>
        <div class="flex items-baseline gap-1"><dd class="font-medium tabular-nums text-ink">{{ $question->answers_count }}</dd><dt>{{ $question->answers_count > 1 ? 'réponses' : 'réponse' }}</dt></div>
        @if ($question->isResolved())
            <div><dt class="sr-only">Statut</dt><dd><x-chip tone="done" class="py-0.5! text-xs!">Résolue</x-chip></dd></div>
        @endif
    </dl>
    <div class="relative z-10 mt-4 flex flex-wrap items-center justify-between gap-3">
        <x-tags :skills="$question->skills" />
        <x-author :user="$question->user" :date="$question->created_at" :relative="$live" :country="$live" class="ml-auto" />
    </div>
</article>
