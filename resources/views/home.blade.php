@php
    use App\Support\Copy;
    // Membre : l'action principale du moment (« Poser une question » dès que les questions existent).
    $memberAction = Route::has('questions.create')
        ? ['label' => 'Poser une question', 'url' => route('questions.create')]
        : ['label' => 'Trouver des devs', 'url' => route('devs.index')];
@endphp

<x-layouts.app>
    <x-slot:head>
        {{-- Ordinateur : l'image portrait est au-dessus de la ligne de flottaison, chargée en priorité.
             Mobile : l'image paysage est sous le CTA, chargée à la demande (le H1 reste l'élément LCP). --}}
        <link rel="preload" as="image" type="image/webp" media="(min-width: 1024px)" fetchpriority="high"
              imagesrcset="/images/hero/portrait-480.webp 480w, /images/hero/portrait-800.webp 800w"
              imagesizes="(min-width: 1280px) 500px, 40vw">
    </x-slot:head>

    <x-slot:hero>
        {{-- Hero (08, modèle stiesas.com) : aplat primary-800, glissé sous la navigation flottante --}}
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

                    <p class="mt-6 max-w-xl text-[17px] leading-relaxed text-white/80">
                        Trouve des missions. Partage tes connaissances. Construis avec les tiens.
                    </p>

                    <div class="mt-8 flex flex-col items-start gap-3">
                        @auth
                            <x-button :href="$memberAction['url']" size="lg" class="w-full sm:w-auto" data-cta="principal">{{ $memberAction['label'] }}</x-button>
                            @if (Route::has('listings.index'))
                                <a href="{{ route('listings.index') }}" class="text-[15px] text-white/70 underline-offset-4 hover:text-white hover:underline">Voir les annonces →</a>
                            @endif
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
                    ['Gratuit, sans carte bancaire', 'ton profil est en ligne en 2 minutes'],
                    ['Pensé pour ton contexte', 'en français, léger en 3G, payé en Mobile Money'],
                    ['Une réputation qui te suit', 'chaque réponse, article ou mission te rapporte des cauris'],
                ] as [$guarantee, $line])
                    <div class="min-w-0">
                        <dt class="font-serif text-lg font-medium text-secondary-400">{{ $guarantee }}</dt>
                        <dd class="mt-1 text-sm text-white/75">{{ $line }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Bande de confiance : chiffres réels, sans arrondi, cache de 10 minutes --}}
        <section aria-label="DevKora en chiffres" class="border-b border-line bg-bg-alt">
            <dl class="mx-auto grid max-w-page grid-cols-2 gap-x-6 gap-y-8 px-4 py-10 text-center sm:grid-cols-3 sm:px-6">
                <div class="flex min-w-0 flex-col items-center">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">Devs inscrits</dt>
                    <dd class="mt-2 font-serif text-xl font-medium text-ink" data-stat="devs">{{ $stats['devs'] }}</dd>
                    <dd class="mt-2 flex -space-x-1.5" aria-hidden="true">
                        @foreach ($avatars as $avatar)
                            <x-avatar :user="$avatar" size="stack" class="ring-bg-alt!" />
                        @endforeach
                    </dd>
                </div>
                <div class="flex min-w-0 flex-col items-center">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">Pays</dt>
                    <dd class="mt-2 font-serif text-xl font-medium text-ink" data-stat="countries">{{ $stats['countries'] }}</dd>
                    <dd class="mt-2 flex max-w-full flex-wrap justify-center gap-0.5 text-base leading-7" aria-hidden="true">
                        @foreach ($stats['flags'] as $flag)
                            <span>{{ $flag }}</span>
                        @endforeach
                    </dd>
                </div>
                <div class="col-span-2 flex min-w-0 flex-col items-center sm:col-span-1">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">Pensé pour</dt>
                    <dd class="mt-2 font-serif text-xl font-medium text-ink">Wave · Orange Money · MTN MoMo</dd>
                </div>
            </dl>
        </section>
    </x-slot:hero>
</x-layouts.app>
