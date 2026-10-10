@php
    use App\Support\Credits;
    $direct = collect(Credits::direct());
    $app = $direct->where('dev', false);
    $tools = $direct->where('dev', true);
    $summary = Credits::summary();
@endphp

<x-layouts.app title="À propos" description="DevKora, le point de rencontre des développeurs africains pour travailler, apprendre et grandir ensemble : l'équipe, les licences et l'usage de l'intelligence artificielle.">
    <div class="mx-auto max-w-3xl">
        <h1 class="text-[38px] sm:text-5xl">Le point de rencontre <em class="font-serif italic text-primary-600 dark:text-secondary-400">des devs africains.</em></h1>
        <p class="mt-4 text-lg text-ink-soft">Trouve des missions. Partage tes connaissances. Construis avec les tiens.</p>

        {{-- L'identité (01) --}}
        <section class="mt-12" aria-labelledby="theme-titre">
            <h2 id="theme-titre" class="text-[28px] sm:text-[32px]">Une plateforme d'échange africaine, pour les devs, par les devs</h2>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2" data-theme-words>
                @foreach ([
                    ['Plateforme d\'échange', 'On partage, on s\'entraide, on collabore et on crée des opportunités.'],
                    ['Africaine', 'Elle comprend les réalités du continent : Mobile Money, connexions lentes, besoins locaux.'],
                    ['Pour les devs', 'Elle répond aux vrais besoins des développeurs, pas à ceux de tout le monde.'],
                    ['Par les devs', 'Les membres participent, partagent et font évoluer la communauté, jusqu\'à la modérer.'],
                ] as [$word, $meaning])
                    <div class="rounded-2xl border border-line bg-surface p-5">
                        <dt class="font-serif text-xl text-ink">{{ $word }}</dt>
                        <dd class="mt-1.5 text-ink-soft">{{ $meaning }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-6 leading-relaxed text-ink-soft">
                <strong class="font-semibold text-ink">DevKora</strong> : « Dev » pour l'univers du développement, « Kora » pour la kora,
                l'instrument emblématique de l'Afrique de l'Ouest. Ses 21 cordes ne sonnent juste qu'ensemble : chaque dev apporte sa note.
            </p>
        </section>

        {{-- L'équipe --}}
        <section class="mt-12" aria-labelledby="equipe-titre">
            <h2 id="equipe-titre" class="text-[28px] sm:text-[32px]">L'équipe</h2>
            <p class="mt-3 text-ink-soft"><strong class="font-semibold text-ink">Baobab Stack</strong> (Sénégal), pour le concours CADEV 2026 organisé par Systalink.</p>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                <li class="rounded-2xl border border-line bg-surface p-5"><p class="font-serif text-xl text-ink">Yaya Niang</p><p class="text-sm text-ink-muted">Représentant de l'équipe</p></li>
                <li class="rounded-2xl border border-line bg-surface p-5"><p class="font-serif text-xl text-ink">Aminata Sall</p><p class="text-sm text-ink-muted">Membre de l'équipe</p></li>
            </ul>
        </section>

        {{-- Licences --}}
        <section class="mt-12" aria-labelledby="licences-titre">
            <h2 id="licences-titre" class="text-[28px] sm:text-[32px]">Les briques et leurs licences</h2>
            <p class="mt-3 text-ink-soft">Toutes sous licence permissive, lues dans <code class="font-mono text-sm">composer.lock</code> et <code class="font-mono text-sm">package-lock.json</code>.</p>
            <x-card padding="p-0" class="mt-5 overflow-hidden">
                <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tableau des licences">
                    <table class="w-full min-w-[30rem] text-left text-sm" data-licenses>
                        <thead class="bg-bg-alt text-ink-muted">
                            <tr><th scope="col" class="px-5 py-3 font-medium">Brique</th><th scope="col" class="px-5 py-3 font-medium">Rôle</th><th scope="col" class="px-5 py-3 font-medium">Licence</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($app as $row)
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
                        Outils de développement ({{ $tools->count() }}) <x-icon name="chevron-down" class="size-4" />
                    </summary>
                    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Outils de développement">
                        <table class="w-full min-w-[30rem] text-left text-sm">
                            <tbody class="divide-y divide-line">
                                @foreach ($tools as $row)
                                    <tr><th scope="row" class="px-5 py-3 font-medium text-ink">{{ $row['name'] }} <span class="font-normal text-ink-muted">{{ $row['version'] }}</span></th><td class="px-5 py-3 text-ink-soft">{{ $row['role'] }}</td><td class="px-5 py-3 text-ink">{{ $row['license'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </x-card>
            <p class="mt-3 text-sm text-ink-muted">Au total, {{ $summary['count'] }} paquets directs et indirects, sous licences {{ implode(', ', $summary['licenses']) }}. Les paquets à double licence sont utilisés sous leur licence permissive.</p>
        </section>

        {{-- Intelligence artificielle : un titre, puis la liste, sans justification (11) --}}
        <section class="mt-12" aria-labelledby="ia-titre">
            <h2 id="ia-titre" class="text-[28px] sm:text-[32px]">Usage de l'intelligence artificielle</h2>
            <ul class="mt-4 space-y-2 text-ink-soft" data-ai>
                <li><strong class="font-semibold text-ink">Claude Code (Anthropic)</strong> : code, tests et revue de code.</li>
                <li><strong class="font-semibold text-ink">ChatGPT (OpenAI)</strong> : image d'illustration de l'accueil.</li>
            </ul>
        </section>

        {{-- Marques de paiement --}}
        <section class="mt-12" aria-labelledby="marques-titre">
            <h2 id="marques-titre" class="text-[28px] sm:text-[32px]">Les moyens de paiement cités</h2>
            <p class="mt-3 text-ink-soft" data-brands>Wave, Orange Money, Free Money, MTN MoMo et Moov Money sont les marques de leurs propriétaires. DevKora n'a aucune affiliation avec eux, et le paiement des missions est simulé : aucun argent réel n'est débité.</p>
        </section>
    </div>
</x-layouts.app>
