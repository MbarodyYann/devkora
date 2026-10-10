@php
    $me = auth()->user();
    $isAuthor = $me?->id === $question->user_id;
@endphp

<x-layouts.app :title="$question->title" :description="$question->excerpt">
    <a href="{{ route('questions.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-left" class="size-4" /> Toutes les questions
    </a>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-6">
            {{-- La question --}}
            <x-card padding="p-5 sm:p-8" class="flex flex-col gap-5 sm:flex-row sm:gap-6">
                <livewire:vote :votable="$question" :key="'q-'.$question->id" />
                <div class="min-w-0 flex-1">
                    <h1 class="break-words text-[30px] leading-[1.15] sm:text-[40px]">{{ $question->title }}</h1>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <x-author :user="$question->user" :date="$question->created_at" verb="Posée" />
                        @if ($question->isResolved())
                            <x-chip tone="done">Résolue</x-chip>
                        @endif
                    </div>
                    <div class="prose-dk mt-6">{!! $question->body_html !!}</div>
                    <x-tags :skills="$question->skills" class="mt-6" />
                </div>
            </x-card>

            {{-- Les réponses : acceptée d'abord, puis par score --}}
            <section aria-labelledby="reponses-titre">
                <h2 id="reponses-titre" class="mb-4 text-[28px] sm:text-[32px]">
                    @if ($answers->isEmpty())
                        Pas encore de réponse
                    @else
                        {{ $answers->count() }} {{ $answers->count() > 1 ? 'réponses' : 'réponse' }}
                    @endif
                </h2>

                @if ($answers->isEmpty())
                    <p class="text-ink-soft">{{ $isAuthor ? 'Ta question est en ligne : les devs qui connaissent le sujet la trouvent par ses tags.' : 'Tu connais la solution ? Ta réponse sera la première.' }}</p>
                @endif

                <ol class="space-y-4">
                    @foreach ($answers as $answer)
                        @php($accepted = $answer->id === $question->accepted_answer_id)
                        <li id="reponse-{{ $answer->id }}" class="scroll-mt-24">
                            <x-card padding="p-5 sm:p-8" @class(['flex flex-col gap-5 sm:flex-row sm:gap-6', 'ring-2 ring-primary-600 dark:ring-secondary-400' => $accepted])>
                                <livewire:vote :votable="$answer" :key="'a-'.$answer->id" />
                                <div class="min-w-0 flex-1">
                                    @if ($accepted)
                                        <x-chip tone="done" class="mb-4">Réponse acceptée</x-chip>
                                    @endif
                                    <div class="prose-dk">{!! $answer->body_html !!}</div>
                                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                                        <x-author :user="$answer->user" :date="$answer->created_at" verb="Répondu" />
                                        @can('accept', $question)
                                            <form method="post" action="{{ route('answers.accept', [$question, $answer]) }}">
                                                @csrf
                                                <x-button type="submit" :variant="$accepted ? 'ghost' : 'secondary'" size="sm">
                                                    @if ($accepted)
                                                        Retirer l'acceptation
                                                    @else
                                                        <x-icon name="check" class="size-4" /> Accepter cette réponse
                                                    @endif
                                                </x-button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </x-card>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Répondre --}}
            @auth
                @can('answer', $question)
                    <x-card id="repondre" class="scroll-mt-24">
                        <h2 class="text-[28px] sm:text-[32px]">{{ $isAuthor ? 'Ajoute ce qui a marché' : 'Ta réponse' }}</h2>
                        <form method="post" action="{{ route('answers.store', $question) }}" class="mt-5 space-y-4" novalidate>
                            @csrf
                            <x-field label="Réponse" name="body" hint="Markdown accepté : `code`, ```php pour un bloc de code, **gras**.">
                                <x-textarea name="body" rows="8" required minlength="20" maxlength="20000" aria-describedby="body-hint" class="font-mono" />
                            </x-field>
                            <div class="flex flex-col items-center gap-3 sm:flex-row sm:justify-end">
                                <p class="text-sm text-ink-muted sm:mr-auto">Une réponse votée te rapporte +10 cauris, une réponse acceptée +15.</p>
                                <x-button type="submit" size="lg" class="w-full sm:w-auto">Publier ma réponse</x-button>
                            </div>
                        </form>
                    </x-card>
                @endcan
            @endauth
        </div>

        <aside class="min-w-0 space-y-6" aria-label="Pour aller plus loin">
            @guest
                {{-- Boîte d'action du visiteur (11, « Le CTA unique du site ») --}}
                <x-card padding="p-6" data-action-box>
                    <h2 class="text-2xl">Tu connais la réponse ?</h2>
                    <p class="mt-2 text-ink-soft">Rejoins la communauté pour répondre, voter et gagner des cauris.</p>
                    <x-button :href="route('register')" class="mt-5 w-full">{{ App\Support\Copy::SIGNUP_CTA }}</x-button>
                    <p class="mt-3 text-center text-sm text-ink-muted">{{ App\Support\Copy::SIGNUP_REASSURANCE }}</p>
                </x-card>
            @endguest
            <x-card padding="p-6">
                <h2 class="text-2xl">L'auteur</h2>
                <a href="{{ route('devs.show', $question->user) }}" class="mt-4 flex min-w-0 items-center gap-3">
                    <x-avatar :user="$question->user" size="sm" />
                    <span class="min-w-0">
                        <span class="block truncate font-medium text-ink">{{ $question->user->name }}</span>
                        @if ($question->user->profile)
                            <span class="block truncate text-sm text-ink-muted">{{ $question->user->profile->city }}, {{ $question->user->profile->countryName() }}</span>
                        @endif
                    </span>
                </a>
                <div class="mt-4 flex items-center gap-3">
                    <x-reputation :points="$question->user->reputation" />
                    <x-level :user="$question->user" />
                </div>
            </x-card>
        </aside>
    </div>
</x-layouts.app>
