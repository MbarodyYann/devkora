@php
    use App\Support\Credits;
@endphp

<x-layouts.app title="À propos" description="DevKora est une plateforme d'échange entre développeurs africains : la présentation, les chiffres, l'équipe, l'usage de l'intelligence artificielle et les licences.">
    <div class="mx-auto max-w-3xl">
        {{-- Présentation : trois phrases, ce que DevKora est et ce qu'on y fait --}}
        <h1 id="page-titre" class="text-[28px] leading-tight sm:text-[36px]">À propos de DevKora</h1>
        <div class="mt-4 space-y-3 leading-relaxed text-ink-soft" data-presentation>
            <p>DevKora est une plateforme d'échange entre développeurs africains.</p>
            <p>Tu y poses tes questions, tu y partages des articles, et tu y trouves des coéquipiers ou des missions confiées par d'autres devs, en FCFA et par Mobile Money (paiement simulé).</p>
            <p>La réputation se compte en cauris, et ce sont les membres qui modèrent.</p>
        </div>
        <p class="mt-3 leading-relaxed text-ink-soft">
            Le nom réunit « Dev » et la kora, l'instrument d'Afrique de l'Ouest. Ses 21 cordes ne sonnent juste qu'ensemble : chaque dev apporte sa note.
        </p>

        {{-- Chiffres réels, sans arrondi, cache de 10 minutes --}}
        <section class="mt-12" aria-labelledby="chiffres-titre">
            <h2 id="chiffres-titre" class="text-[22px] sm:text-[26px]">En chiffres</h2>
            <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-6 rounded-2xl border border-line bg-surface p-5 sm:grid-cols-4" aria-label="DevKora en chiffres">
                <div class="min-w-0">
                    <dt class="text-sm text-ink-muted">Devs inscrits</dt>
                    <dd class="mt-1 font-serif text-[28px] font-medium leading-none text-ink" data-stat="devs">{{ $stats['devs'] }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-sm text-ink-muted">Pays</dt>
                    <dd class="mt-1 font-serif text-[28px] font-medium leading-none text-ink" data-stat="countries">{{ $stats['countries'] }}</dd>
                    {{-- Drapeaux sur une seule ligne : 5 au plus, puis le reste en texte. --}}
                    @php($flags = collect($stats['flags']))
                    <dd class="mt-2 flex flex-nowrap items-center gap-1 text-sm leading-6" aria-hidden="true" data-flags>
                        @foreach ($flags->take(5) as $flag)
                            <span>{{ $flag }}</span>
                        @endforeach
                        @if ($flags->count() > 5)
                            <span class="ml-0.5 font-medium text-ink-muted">+{{ $flags->count() - 5 }}</span>
                        @endif
                    </dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-sm text-ink-muted">Questions résolues</dt>
                    <dd class="mt-1 font-serif text-[28px] font-medium leading-none text-ink" data-stat="resolved">{{ $stats['resolved'] }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-sm text-ink-muted">Articles partagés</dt>
                    <dd class="mt-1 font-serif text-[28px] font-medium leading-none text-ink" data-stat="articles">{{ $stats['articles'] }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-sm text-ink-muted">Tarifs, compétences recherchées et activité : <a href="{{ route('pulse') }}" class="font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">voir le Pouls</a>.</p>
        </section>

        {{-- L'équipe --}}
        <section class="mt-12" aria-labelledby="equipe-titre">
            <h2 id="equipe-titre" class="text-[22px] sm:text-[26px]">L'équipe</h2>
            <p class="mt-3 text-ink-soft"><strong class="font-semibold text-ink">Baobab Stack</strong> (Sénégal), pour le concours CADEV 2026 organisé par Systalink.</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-3" data-team>
                @foreach (['Yaya Niang', 'Aminata Sall', 'Baydi Sao'] as $member)
                    <li class="min-w-0 rounded-2xl border border-line bg-surface p-5"><p class="font-serif text-xl text-ink">{{ $member }}</p><p class="text-sm text-ink-muted">Membre de l'équipe</p></li>
                @endforeach
            </ul>
        </section>

        {{-- Intelligence artificielle : un titre, puis la liste, sans justification (11) --}}
        <section class="mt-12" aria-labelledby="ia-titre">
            <h2 id="ia-titre" class="text-[22px] sm:text-[26px]">Usage de l'intelligence artificielle</h2>
            <ul class="mt-4 space-y-2 text-ink-soft" data-ai>
                <li><strong class="font-semibold text-ink">Claude Code (Anthropic)</strong> : code, tests et revue de code.</li>
                <li><strong class="font-semibold text-ink">ChatGPT (OpenAI)</strong> : image d'illustration de l'accueil.</li>
            </ul>
        </section>

        {{-- Licences --}}
        <section class="mt-12" aria-labelledby="licences-titre">
            <h2 id="licences-titre" class="text-[22px] sm:text-[26px]">Licences</h2>
            <p class="mt-3 text-ink-soft">Toutes les briques sont sous licence permissive, lues dans <code class="font-mono text-sm">composer.lock</code> et <code class="font-mono text-sm">package-lock.json</code>.</p>
            <x-card padding="p-0" class="mt-5 overflow-hidden">
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tableau des licences">
                    <table class="w-full min-w-[30rem] text-left text-sm" data-licenses>
                        <thead class="bg-bg-alt text-ink-muted">
                            <tr><th scope="col" class="px-5 py-3 font-medium">Brique</th><th scope="col" class="px-5 py-3 font-medium">Rôle</th><th scope="col" class="px-5 py-3 font-medium">Licence</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($appCredits as $row)
                                <tr><th scope="row" class="px-5 py-3 font-medium text-ink">{{ $row['name'] }} <span class="font-normal text-ink-muted">{{ $row['version'] }}</span></th><td class="px-5 py-3 text-ink-soft">{{ $row['role'] }}</td><td class="px-5 py-3 text-ink">{{ $row['license'] }}</td></tr>
                            @endforeach
                            @foreach (Credits::EXTRA as $row)
                                <tr><th scope="row" class="px-5 py-3 font-medium text-ink">{{ $row['name'] }}</th><td class="px-5 py-3 text-ink-soft">{{ $row['role'] }}</td><td class="px-5 py-3 text-ink">{{ $row['license'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <details class="border-t border-line" data-dev-tools>
                    <summary class="flex min-h-12 cursor-pointer items-center justify-between gap-3 px-5 font-medium text-ink [&::-webkit-details-marker]:hidden">
                        Outils de développement ({{ $toolCredits->count() }}) <x-icon name="chevron-down" class="size-4" />
                    </summary>
                    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Outils de développement">
                        <table class="w-full min-w-[30rem] text-left text-sm">
                            <tbody class="divide-y divide-line">
                                @foreach ($toolCredits as $row)
                                    <tr><th scope="row" class="px-5 py-3 font-medium text-ink">{{ $row['name'] }} <span class="font-normal text-ink-muted">{{ $row['version'] }}</span></th><td class="px-5 py-3 text-ink-soft">{{ $row['role'] }}</td><td class="px-5 py-3 text-ink">{{ $row['license'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </x-card>
            <p class="mt-3 text-sm text-ink-muted">Au total, {{ $creditsSummary['count'] }} paquets directs et indirects, sous licences {{ implode(', ', $creditsSummary['licenses']) }}. Les paquets à double licence sont utilisés sous leur licence permissive.</p>
            <p class="mt-3 text-sm text-ink-muted" data-brands>Wave, Orange Money, Free Money, MTN MoMo et Moov Money sont les marques de leurs propriétaires. DevKora n'a aucune affiliation avec eux, et le paiement des missions est simulé : aucun argent réel n'est débité.</p>
        </section>
    </div>
</x-layouts.app>
