@php
    $me = auth()->user();
    $tiers = [
        ['points' => 0, 'title' => 'Membre', 'items' => ['Poser des questions et y répondre', 'Écrire des articles', 'Voter +1 et « Utile »', 'Publier des annonces et des événements', 'Signaler un contenu']],
        ['points' => config('privileges.downvote'), 'title' => 'Vote négatif', 'items' => ['Voter −1 sur une réponse peu utile (−2 cauris pour son auteur)']],
        ['points' => config('privileges.moderate'), 'title' => 'Modération communautaire', 'items' => ['Accéder à la file des signalements', 'Masquer, rétablir ou classer sans suite un contenu signalé'], 'note' => 'Jamais sur ton propre contenu, ni sur un contenu que tu as signalé.'],
        ['points' => config('privileges.retag'), 'title' => 'Modifier les tags', 'items' => ['Corriger les tags de la question d\'un autre membre, pour qu\'elle soit trouvée']],
    ];
    $next = $me ? collect($tiers)->first(fn ($tier) => $tier['points'] > $me->reputation) : null;
@endphp

<x-layouts.app title="Privilèges" description="Tes cauris débloquent de nouveaux rôles : la communauté se gère elle-même.">
    <div class="mx-auto max-w-3xl">
        <h1 class="text-[38px] sm:text-5xl">Plus tu aides, <em class="font-serif italic text-primary-600 dark:text-secondary-400">plus tu comptes.</em></h1>
        <p class="mt-3 text-ink-soft">Tes cauris débloquent de nouveaux rôles : la communauté se gère elle-même.</p>

        @if ($me)
            <x-card class="mt-8" data-progress>
                <p class="flex items-center gap-2 text-ink"><x-cauri class="size-6 text-secondary-400" /> Tu as <strong class="font-semibold">{{ App\Support\Money::format($me->reputation) }} cauris</strong>.</p>
                @if ($next)
                    @php($from = collect($tiers)->last(fn ($tier) => $tier['points'] <= $me->reputation)['points'])
                    @php($percent = (int) round(max(0, $me->reputation - $from) / ($next['points'] - $from) * 100))
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-bg-alt ring-1 ring-line" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="Progression vers {{ $next['title'] }}">
                        <div class="h-full rounded-full bg-secondary-400" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-ink-muted">Encore {{ $next['points'] - $me->reputation }} cauris pour débloquer « {{ $next['title'] }} ».</p>
                @else
                    <p class="mt-2 text-sm text-ink-muted">Tu as débloqué tous les privilèges. Merci pour ce que tu apportes à la communauté.</p>
                @endif
            </x-card>
        @endif

        <ol class="mt-8 space-y-4">
            @foreach ($tiers as $tier)
                @php($unlocked = $me && $me->reputation >= $tier['points'])
                <li>
                    <x-card padding="p-5 sm:p-6" @class(['ring-2 ring-secondary-400' => $unlocked && $tier['points'] > 0])>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-2xl">{{ $tier['title'] }}</h2>
                            <span class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-ink"><x-cauri /> {{ $tier['points'] }}</span>
                                @if ($unlocked && $tier['points'] > 0)
                                    <x-chip tone="done">Débloqué</x-chip>
                                @endif
                            </span>
                        </div>
                        <ul class="mt-3 space-y-1.5 text-ink-soft">
                            @foreach ($tier['items'] as $item)
                                <li class="flex items-start gap-2"><x-icon name="check" class="mt-1 size-4 shrink-0 text-primary-600 dark:text-secondary-400" /> {{ $item }}</li>
                            @endforeach
                        </ul>
                        @isset($tier['note'])
                            <p class="mt-3 text-sm text-ink-muted" data-limit>{{ $tier['note'] }}</p>
                        @endisset
                    </x-card>
                </li>
            @endforeach
        </ol>

        <x-card class="mt-8">
            <h2 class="text-2xl">Comment gagner des cauris</h2>
            <dl class="mt-4 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                @foreach ([['Réponse votée +1', '+10'], ['Réponse acceptée', '+15'], ['Article jugé « Utile »', '+5'], ['Mission terminée', '+20'], ['Avis reçu (5, 4, 3 étoiles)', '+30, +20, +10'], ['Réponse votée −1', '−2']] as [$label, $points])
                    <div class="flex justify-between gap-3 border-b border-line py-1.5"><dt class="text-ink-soft">{{ $label }}</dt><dd class="font-semibold text-ink">{{ $points }}</dd></div>
                @endforeach
            </dl>
        </x-card>
    </div>
</x-layouts.app>
