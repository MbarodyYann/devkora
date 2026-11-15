@php
    use App\Support\Copy;
    use App\Support\Credits;
    use App\Support\Money;
@endphp

<x-layouts.app title="À propos" description="DevKora, le point de rencontre des développeurs africains pour travailler, apprendre et grandir ensemble : l'équipe, les licences et l'usage de l'intelligence artificielle.">
    <x-slot:head>
        {{-- Ordinateur : l'image portrait est au-dessus de la ligne de flottaison, chargée en priorité.
             Mobile : l'image paysage est sous le CTA, chargée à la demande (le H1 reste l'élément LCP). --}}
        <link rel="preload" as="image" type="image/webp" media="(min-width: 1024px)" fetchpriority="high"
              imagesrcset="/images/hero/portrait-480.webp 480w, /images/hero/portrait-800.webp 800w"
              imagesizes="(min-width: 1280px) 500px, 40vw">
    </x-slot:head>

    <x-slot:hero>
        {{-- 1. Hero (08, modèle stiesas.com) : aplat primary-800, glissé sous la navigation flottante --}}
        <section id="hero" aria-labelledby="hero-titre" class="relative -mt-[68px] overflow-hidden bg-primary-800 pt-[68px] text-white sm:-mt-[76px] sm:pt-[76px] dark:bg-primary-950">
            {{-- Décor : 3 arcs concentriques, trait blanc à 6 %, coin inférieur gauche --}}
            <svg class="pointer-events-none absolute bottom-0 left-0 h-[560px] w-[560px] sm:h-[760px] sm:w-[760px]" viewBox="0 0 760 760" fill="none" aria-hidden="true">
                @foreach ([340, 500, 660] as $radius)
                    <circle cx="0" cy="760" r="{{ $radius }}" stroke="white" stroke-opacity=".06" stroke-width="1" />
                @endforeach
            </svg>

            <div class="relative mx-auto grid max-w-page items-center gap-10 px-4 pb-14 pt-10 sm:px-6 sm:pb-20 sm:pt-16 lg:grid-cols-[55fr_45fr] lg:gap-12 lg:pb-24">
                <div class="min-w-0">
                    <h1 id="hero-titre" class="text-balance text-[38px] leading-[1.08] text-white sm:text-[56px]">
                        Le point de rencontre
                        <em class="block font-serif italic text-secondary-400">des devs africains.</em>
                    </h1>

                    <p class="mt-6 max-w-xl text-balance text-[17px] leading-relaxed text-white/80">
                        Trouve des missions. Partage tes connaissances. Construis avec les tiens.
                    </p>

                    <div class="mt-8 flex flex-col items-start gap-3">
                        @auth
                            {{-- Le membre a déjà « Publier » dans la navigation : un lien suffit. --}}
                            <a href="{{ route('questions.index') }}" class="text-[15px] font-semibold text-white underline decoration-secondary-400 decoration-2 underline-offset-4 hover:text-secondary-400" data-member-link>Voir les questions →</a>
                        @else
                            <x-button :href="route('register')" size="lg" class="w-full sm:w-auto" data-cta="principal">{{ Copy::SIGNUP_CTA }}</x-button>
                            <p class="text-sm text-white/70">{{ Copy::SIGNUP_REASSURANCE }}</p>
                        @endauth
                    </div>
                </div>

                {{-- Image générée par IA (ChatGPT) : portrait 4:5 sur ordinateur, paysage 16:9 sur mobile, sous le CTA.
                     Sources et conversion : scripts/hero-images.php --}}
                <picture class="block min-w-0">
                    <source media="(min-width: 1024px)" type="image/webp"
                            srcset="/images/hero/portrait-480.webp 480w, /images/hero/portrait-800.webp 800w"
                            sizes="(min-width: 1280px) 500px, 40vw"
                            width="800" height="1000">
                    <img src="/images/hero/paysage-800.webp"
                         srcset="/images/hero/paysage-480.webp 480w, /images/hero/paysage-800.webp 800w"
                         sizes="(min-width: 640px) calc(100vw - 48px), calc(100vw - 32px)"
                         width="800" height="450"
                         alt="Deux développeurs travaillent ensemble sur du code"
                         loading="lazy" decoding="async"
                         class="aspect-video w-full rounded-2xl border border-white/10 object-cover lg:aspect-[4/5]">
                </picture>
            </div>
        </section>

        {{-- Bande de garanties, collée sous le hero --}}
        <section aria-label="Nos garanties" class="bg-primary-900 text-white">
            <dl class="mx-auto grid max-w-page gap-6 px-4 py-8 sm:grid-cols-3 sm:px-6">
                @foreach ([
                    ['Gratuit, sans abonnement', 'ton profil est en ligne en 2 minutes'],
                    ['Pensé pour ton contexte', 'en français, léger en 3G, missions payées en Mobile Money'],
                    ['Une réputation qui te suit', 'chaque réponse, article ou mission te rapporte des cauris'],
                ] as [$guarantee, $line])
                    <div class="min-w-0">
                        <dt class="font-serif text-lg font-medium text-secondary-400">{{ $guarantee }}</dt>
                        <dd class="mt-1 text-sm text-white/75">{{ $line }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- 2. Bande de confiance : chiffres réels, sans arrondi, cache de 10 minutes --}}
        <section aria-label="DevKora en chiffres" class="border-b border-line bg-bg-alt">
            <dl class="mx-auto grid max-w-page grid-cols-2 gap-x-6 gap-y-8 px-4 py-10 text-center sm:px-6 lg:grid-cols-4">
                <div class="flex min-w-0 flex-col items-center">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">Devs inscrits</dt>
                    <dd class="mt-2 font-serif text-xl font-medium text-ink" data-stat="devs">{{ $stats['devs'] }}</dd>
                </div>
                <div class="flex min-w-0 flex-col items-center">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">Pays</dt>
                    <dd class="mt-2 font-serif text-xl font-medium text-ink" data-stat="countries">{{ $stats['countries'] }}</dd>
                    {{-- Drapeaux sur une seule ligne : 5 sous 360 px, 6 sous 640 px, 8 au-dessus, puis le reste en texte. --}}
                    @php($flags = collect($stats['flags']))
                    <dd class="mt-2 flex flex-nowrap items-center gap-1 text-base leading-7" aria-hidden="true" data-flags>
                        @foreach ($flags->take(8) as $flag)
                            <span @class(['max-[360px]:hidden' => $loop->index === 5, 'max-sm:hidden' => $loop->index >= 6])>{{ $flag }}</span>
                        @endforeach
                        @if ($flags->count() > 5)
                            <span class="ml-1 hidden text-sm font-semibold text-ink-muted max-[360px]:inline">+{{ $flags->count() - 5 }}</span>
                        @endif
                        @if ($flags->count() > 6)
                            <span class="ml-1 text-sm font-semibold text-ink-muted max-[360px]:hidden sm:hidden">+{{ $flags->count() - 6 }}</span>
                        @endif
                        @if ($flags->count() > 8)
                            <span class="ml-1 hidden text-sm font-semibold text-ink-muted sm:inline">+{{ $flags->count() - 8 }}</span>
                        @endif
                    </dd>
                </div>
                <div class="flex min-w-0 flex-col items-center">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">Questions résolues</dt>
                    <dd class="mt-2 font-serif text-xl font-medium text-ink" data-stat="resolved">{{ $stats['resolved'] }}</dd>
                </div>
                <div class="flex min-w-0 flex-col items-center">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">Articles partagés</dt>
                    <dd class="mt-2 font-serif text-xl font-medium text-ink" data-stat="articles">{{ $stats['articles'] }}</dd>
                </div>
            </dl>
        </section>
    </x-slot:hero>

    {{-- 3. Les trois piliers (08, 11) : ce qu'on vient faire ici --}}
    <section class="pt-16 sm:pt-24" aria-labelledby="piliers-titre">
        <h2 id="piliers-titre" class="text-[32px] sm:text-[44px]">Ici, on échange.</h2>
        <ul class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3" data-pillars>
            @foreach ([
                ['Entraide', 'message', 'Pose tes questions, partage tes tutoriels : la meilleure réponse reste trouvable par tous.', 'Voir les questions →', 'questions.index'],
                ['Communauté', 'users', 'Trouve des coéquipiers d\'autres pays ou confie une mission à un dev de confiance.', 'Voir les annonces →', 'listings.index'],
                ['Le Pouls', 'chart', 'Combien facturer ? Quelles compétences sont recherchées ? Les chiffres réels de la communauté.', 'Voir le Pouls →', 'pulse'],
            ] as [$pillar, $icon, $line, $link, $route])
                <li class="min-w-0">
                    <a href="{{ route($route) }}" class="group flex h-full flex-col rounded-2xl border border-line bg-surface p-6 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:hover:translate-y-0 sm:rounded-card sm:p-8">
                        <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-900 dark:text-secondary-400"><x-icon :name="$icon" class="size-6" /></span>
                        <h3 class="mt-5 text-[28px]">{{ $pillar }}</h3>
                        <p class="mt-2 text-ink-soft">{{ $line }}</p>
                        <span class="mt-auto pt-5 font-semibold text-primary-600 group-hover:underline dark:text-primary-300">{{ $link }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 4. Aperçu du Pouls : aucun chiffre inventé, la section n'apparaît qu'avec des tarifs réels --}}
    @if ($rates !== [])
        <section class="pt-16 sm:pt-24" aria-labelledby="pouls-titre" data-pulse-preview>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <h2 id="pouls-titre" class="text-[32px] sm:text-[44px]">Combien facturer ? <em class="font-serif italic text-primary-600 dark:text-secondary-400">Le marché te répond.</em></h2>
                    <p class="mt-3 text-ink-soft">Tarifs médians en FCFA par compétence, calculés à partir des missions entre devs et des prix proposés.</p>
                </div>
                <a href="{{ route('pulse') }}" class="font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">Voir tous les tarifs →</a>
            </div>
            <ul class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($rates as $rate)
                    <li class="min-w-0 rounded-2xl border border-line bg-surface p-6 shadow-card sm:rounded-card">
                        <h3 class="truncate font-sans text-[15px] font-semibold text-ink-soft">{{ $rate['skill'] }}</h3>
                        <p class="mt-3 font-serif text-[28px] font-medium leading-none text-ink" data-rate="{{ $rate['slug'] }}">{{ Money::format($rate['median']) }} <span class="font-sans text-sm font-medium text-ink-muted">FCFA</span></p>
                        <p class="mt-3 text-sm text-ink-muted">Fourchette habituelle : {{ Money::format($rate['low']) }} à {{ Money::fcfa($rate['high']) }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Par les devs : contributeurs du mois --}}
    @if ($contributors->isNotEmpty())
        <section class="pt-16 sm:pt-24" aria-labelledby="contributeurs-titre">
            <h2 id="contributeurs-titre" class="max-w-2xl text-[32px] sm:text-[44px]">Ils ont fait avancer la communauté ce mois-ci.</h2>
            <p class="mt-3 max-w-2xl text-ink-soft">Les cauris gagnés en 30 jours : réponses utiles, articles, missions livrées. <a href="{{ route('privileges') }}" class="font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">À quoi servent les cauris ?</a></p>
            <x-contributors :contributors="$contributors" class="mt-8" />
        </section>
    @endif

    {{-- 5. L'identité (01) --}}
    <section class="pt-16 sm:pt-24" aria-labelledby="theme-titre">
        <h2 id="theme-titre" class="max-w-3xl text-[32px] sm:text-[44px]">Une plateforme d'échange africaine, pour les devs, par les devs</h2>
        <p class="mt-3 max-w-3xl text-ink-soft" data-identity>« Africaine » dit qui nous sommes. « D'échange » dit ce que tu viens y faire.</p>
        <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-theme-words>
            @foreach ([
                ['Plateforme d\'échange', 'On partage, on s\'entraide, on collabore et on crée des opportunités.'],
                ['Africaine', 'Elle comprend les réalités du continent : Mobile Money, connexions lentes, besoins locaux.'],
                ['Pour les devs', 'Elle répond aux vrais besoins des développeurs, pas à ceux de tout le monde.'],
                ['Par les devs', 'Les membres participent, partagent et font évoluer la communauté, jusqu\'à la modérer.'],
            ] as [$word, $meaning])
                <div class="min-w-0 rounded-2xl border border-line bg-surface p-5">
                    <dt class="font-serif text-xl text-ink">{{ $word }}</dt>
                    <dd class="mt-1.5 text-ink-soft">{{ $meaning }}</dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-6 max-w-3xl leading-relaxed text-ink-soft">
            <strong class="font-semibold text-ink">DevKora</strong> : « Dev » pour l'univers du développement, « Kora » pour la kora,
            l'instrument emblématique de l'Afrique de l'Ouest. Ses 21 cordes ne sonnent juste qu'ensemble : chaque dev apporte sa note.
        </p>
    </section>

    {{-- L'équipe --}}
    <section class="pt-16 sm:pt-24" aria-labelledby="equipe-titre">
        <h2 id="equipe-titre" class="text-[32px] sm:text-[44px]">L'équipe</h2>
        <p class="mt-3 text-ink-soft"><strong class="font-semibold text-ink">Baobab Stack</strong> (Sénégal), pour le concours CADEV 2026 organisé par Systalink.</p>
        <ul class="mt-6 grid max-w-3xl gap-3 sm:grid-cols-2">
            <li class="rounded-2xl border border-line bg-surface p-5"><p class="font-serif text-xl text-ink">Yaya Niang</p><p class="text-sm text-ink-muted">Représentant de l'équipe</p></li>
            <li class="rounded-2xl border border-line bg-surface p-5"><p class="font-serif text-xl text-ink">Aminata Sall</p><p class="text-sm text-ink-muted">Membre de l'équipe</p></li>
        </ul>
    </section>

    {{-- Intelligence artificielle : un titre, puis la liste, sans justification (11) --}}
    <section class="pt-16 sm:pt-24" aria-labelledby="ia-titre">
        <h2 id="ia-titre" class="text-[32px] sm:text-[44px]">Usage de l'intelligence artificielle</h2>
        <ul class="mt-4 space-y-2 text-ink-soft" data-ai>
            <li><strong class="font-semibold text-ink">Claude Code (Anthropic)</strong> : code, tests et revue de code.</li>
            <li><strong class="font-semibold text-ink">ChatGPT (OpenAI)</strong> : image d'illustration de l'accueil.</li>
        </ul>
    </section>

    {{-- Licences --}}
    <section class="pt-16 sm:pt-24" aria-labelledby="licences-titre">
        <h2 id="licences-titre" class="text-[32px] sm:text-[44px]">Les briques et leurs licences</h2>
        <p class="mt-3 text-ink-soft">Toutes sous licence permissive, lues dans <code class="font-mono text-sm">composer.lock</code> et <code class="font-mono text-sm">package-lock.json</code>.</p>
        <x-card padding="p-0" class="mt-6 max-w-3xl overflow-hidden">
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
        <p class="mt-3 max-w-3xl text-sm text-ink-muted">Au total, {{ $creditsSummary['count'] }} paquets directs et indirects, sous licences {{ implode(', ', $creditsSummary['licenses']) }}. Les paquets à double licence sont utilisés sous leur licence permissive.</p>
    </section>

    {{-- Marques de paiement --}}
    <section class="pt-16 sm:pt-24" aria-labelledby="marques-titre">
        <h2 id="marques-titre" class="text-[32px] sm:text-[44px]">Les moyens de paiement cités</h2>
        <p class="mt-3 max-w-3xl text-ink-soft" data-brands>Wave, Orange Money, Free Money, MTN MoMo et Moov Money sont les marques de leurs propriétaires. DevKora n'a aucune affiliation avec eux, et le paiement des missions est simulé : aucun argent réel n'est débité.</p>
    </section>

    {{-- 6. CTA final (08, 11) : le même bouton que le hero, pour le visiteur seulement --}}
    @guest
        <section class="relative mt-16 overflow-hidden rounded-2xl bg-primary-800 px-6 py-14 text-center text-white sm:mt-24 sm:rounded-card sm:px-10 sm:py-20 dark:bg-primary-950" aria-labelledby="final-titre" data-final-cta>
            <svg class="pointer-events-none absolute bottom-0 left-0 h-[420px] w-[420px]" viewBox="0 0 760 760" fill="none" aria-hidden="true">
                @foreach ([340, 500, 660] as $radius)
                    <circle cx="0" cy="760" r="{{ $radius }}" stroke="white" stroke-opacity=".06" stroke-width="1" />
                @endforeach
            </svg>
            <div class="relative">
                <h2 id="final-titre" class="mx-auto max-w-2xl text-[34px] text-white sm:text-[48px]">Ne reste plus seul <em class="font-serif italic text-secondary-400">face à ton code.</em></h2>
                <x-button :href="route('register')" size="lg" class="mt-8 w-full sm:w-auto" data-cta="final">{{ Copy::SIGNUP_CTA }}</x-button>
                <p class="mt-3 text-sm text-white/70">{{ Copy::SIGNUP_REASSURANCE }}</p>
            </div>
        </section>
    @endguest
</x-layouts.app>
