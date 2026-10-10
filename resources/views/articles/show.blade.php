<x-layouts.app :title="$article->title" :description="$article->excerpt">
    <article class="mx-auto max-w-[44rem]">
        <a href="{{ route('articles.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
            <x-icon name="arrow-left" class="size-4" /> Tous les articles
        </a>

        <header>
            <x-chip>{{ $article->category->label() }}</x-chip>
            <h1 class="mt-4 break-words text-[34px] leading-[1.12] sm:text-5xl">{{ $article->title }}</h1>
            <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-line pb-6 text-sm text-ink-muted">
                <x-author :user="$article->user" :date="$article->created_at" verb="Publié" />
                <span class="inline-flex items-center gap-1"><x-icon name="clock" class="size-4" /> {{ $article->reading_minutes }} min de lecture</span>
            </div>
        </header>

        {{-- Lecture confortable : colonne d'environ 70 caractères, titres en Newsreader, code coloré. --}}
        <div class="prose-dk prose-article mt-8">{!! $article->body_html !!}</div>

        <x-tags :skills="$article->skills" route="articles.index" class="mt-10" />

        <footer class="mt-10 flex flex-col gap-4 rounded-2xl border border-line bg-bg-alt p-5 sm:flex-row sm:items-center sm:justify-between sm:rounded-card sm:p-6">
            <p class="text-ink">Cet article t'a aidé ? Dis-le à {{ $article->user->firstName() }}.</p>
            <livewire:useful :article="$article" />
        </footer>

        @guest
            <x-card padding="p-6" class="mt-6 text-center" data-action-box>
                <h2 class="text-2xl">Toi aussi, tu as appris quelque chose qui peut servir.</h2>
                <p class="mt-2 text-ink-soft">Rejoins la communauté pour écrire tes articles et gagner des cauris.</p>
                <x-button :href="route('register')" class="mt-5">{{ App\Support\Copy::SIGNUP_CTA }}</x-button>
                <p class="mt-3 text-sm text-ink-muted">{{ App\Support\Copy::SIGNUP_REASSURANCE }}</p>
            </x-card>
        @endguest

        @if ($more->isNotEmpty())
            <section class="mt-12" aria-labelledby="plus-titre">
                <h2 id="plus-titre" class="text-[28px]">Du même auteur</h2>
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($more as $other)
                        <li class="flex items-start justify-between gap-3 py-3">
                            <a href="{{ route('articles.show', $other) }}" class="min-w-0 font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $other->title }}</a>
                            <span class="shrink-0 text-sm text-ink-muted">{{ $other->reading_minutes }} min</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
</x-layouts.app>
