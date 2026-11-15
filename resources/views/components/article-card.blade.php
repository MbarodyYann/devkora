@props(['article'])

{{-- Carte article (08) : catégorie, titre en Newsreader 22 px, extrait, temps de lecture, nombre de « Utile »,
     auteur avec avatar. --}}
<article {{ $attributes->merge(['class' => 'relative flex h-full min-w-0 flex-col rounded-2xl border border-line bg-surface p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:hover:translate-y-0 sm:rounded-card sm:p-6']) }}>
    <x-chip class="self-start">{{ $article->category->label() }}</x-chip>
    <h3 class="mt-4 font-serif text-[22px] leading-snug">
        <a href="{{ route('articles.show', $article) }}" class="text-ink after:absolute after:inset-0 hover:text-primary-600 dark:hover:text-primary-300">{{ $article->title }}</a>
    </h3>
    <p class="mt-3 line-clamp-3 break-words text-ink-soft">{{ $article->excerpt }}</p>
    <div class="mt-auto pt-5">
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-muted">
            <span class="inline-flex items-center gap-1"><x-icon name="clock" class="size-4" /> {{ $article->reading_minutes }} min de lecture</span>
            <span>{{ $article->score }} × Utile</span>
        </p>
        <div class="mt-4 flex items-center gap-2 border-t border-line pt-4 text-sm">
            <x-avatar :user="$article->user" size="xs" />
            <span class="min-w-0 truncate font-medium text-ink">{{ $article->user->name }}</span>
            <time class="ml-auto shrink-0 text-ink-muted" datetime="{{ $article->created_at->toIso8601String() }}">{{ $article->created_at->translatedFormat('j M Y') }}</time>
        </div>
    </div>
</article>
