@props(['title' => null, 'description' => null, 'pageHero' => null, 'noindex' => false])

@php
    use App\Support\Brand;
    use App\Support\Copy;
    use App\Support\Navigation;

    $me = auth()->user()?->loadMissing('profile');
    // Cloche : nombre de non lues et les 8 dernières.
    $unreadCount = $me ? $me->unreadNotifications()->count() : 0;
    $latestNotifications = $me ? $me->notifications()->limit(8)->get() : collect();
    $mainNav = Navigation::main();
    $bottomNav = Navigation::bottom();
    $meActive = $me ? request()->routeIs('devs.show', 'profile.edit') && request()->route('user')?->is($me) : request()->routeIs('login', 'register');
    // Bandeau indigo sous la navigation : hero de l'accueil (slot) ou en-tête compact d'une page de liste.
    $hasHero = isset($hero) || $pageHero;
    // Livewire (qui embarque Alpine) seulement si la page a rendu un composant Livewire ; sinon Alpine seul,
    // bien plus léger en 3G. Le contenu est rendu avant le gabarit : l'indicateur est fiable ici.
    $usesLivewire = Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest;
    $fullTitle = $title ? $title.' · '.Brand::NAME : Brand::NAME.' · Le point de rencontre des devs africains';
    $metaDescription = Str::limit($description ?? $pageHero['subtitle'] ?? Brand::TAGLINE, 160);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if ($noindex)
        <meta name="robots" content="noindex">
    @endif
    <meta name="theme-color" content="#314A82">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Anti-flash : applique le thème avant le premier rendu (choix gardé, sinon préférence système). --}}
    <script>
        (function () {
            var theme = null;
            try { theme = localStorage.getItem('theme'); } catch (e) {}
            if (theme === 'dark' || (theme === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preload" href="/fonts/newsreader-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/schibsted-grotesk-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    @if ($usesLivewire)
        @vite('resources/css/app.css')
        @livewireStyles
    @else
        @vite(['resources/css/app.css', 'resources/js/alpine.js'])
    @endif
    {{ $head ?? '' }}
</head>
<body class="min-h-dvh pb-safe lg:pb-0">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-btn focus:bg-surface focus:px-4 focus:py-2 focus:text-ink focus:shadow-card">
        Aller au contenu
    </a>

    @if ($demoPersona = App\Support\Demo::personaName($me))
        <p role="status" data-demo-banner class="bg-primary-950 px-4 py-1.5 text-center text-[13px] text-white/85">
            Tu explores DevKora avec le compte de démonstration de {{ $demoPersona }}.
        </p>
    @endif

    {{-- Navigation flottante, à 12 px du haut. Sur un bandeau indigo : transparente tout en haut, puis opaque
         (primary-800) dès le premier pixel de défilement. Sous 1 024 px, une fois la page défilée, elle se colle
         au bord haut, sans espace où le contenu se lirait au-dessus ; sa hauteur totale ne change pas. --}}
    {{-- Sous 1 024 px, l'espace au-dessus de la barre a un fond opaque de la couleur du haut de page (bandeau indigo
         ou fond) : même avant le chargement du JavaScript, aucun texte ne se lit au-dessus de la navigation. --}}
    <div @class([
             'sticky top-0 z-40 px-2 pt-3 sm:px-6',
             'max-lg:bg-primary-800 dark:max-lg:bg-primary-950' => $hasHero,
             'max-lg:bg-bg' => ! $hasHero,
         ])
         x-data="{ scrolled: window.scrollY > 0 }"
         x-on:scroll.window.passive="scrolled = window.scrollY > 0"
         x-bind:class="scrolled && 'max-lg:px-0! max-lg:pt-0!'">
        <header @class([
                    'mx-auto flex h-14 max-w-page items-center gap-2 rounded-nav px-3 sm:h-16 sm:gap-3 sm:px-4 lg:gap-6',
                    'nav-glass' => ! $hasHero,
                    'nav-on-hero' => $hasHero,
                ])
                x-bind:class="{ 'max-lg:h-[68px]! max-lg:rounded-none max-lg:border-x-0 max-lg:border-t-0 max-lg:pt-3 sm:max-lg:h-[76px]!': scrolled }">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ Brand::NAME }}, accueil">
                <x-logo class="max-[379px]:text-[22px]" />
            </a>

            <nav class="hidden flex-1 items-center justify-center gap-1 lg:flex" aria-label="Navigation principale">
                @foreach ($mainNav as $item)
                    @php($active = request()->routeIs($item['active']))
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class([
                           'relative rounded-btn px-3 py-2 font-medium transition',
                           'text-ink' => $active,
                           'text-ink-muted hover:text-ink' => ! $active,
                       ])>
                        {{ $item['label'] }}
                        @if ($active)
                            <span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded-full bg-secondary-400" aria-hidden="true"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2 lg:ml-0 lg:gap-3">
                <x-theme-toggle class="max-lg:hidden" />

                @auth
                    {{-- « Publier » (08) : menu global des membres ; les en-têtes de liste gardent leur propre action. --}}
                    @if ($publishMenu = Navigation::publish())
                        <div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false" data-publish-menu>
                            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-haspopup="true"
                                    class="btn-amber inline-flex min-h-9 items-center gap-1.5 rounded-btn px-3 text-[13px] font-semibold lg:min-h-10 lg:px-3.5 lg:text-sm">
                                <x-icon name="plus" class="size-4" /> Publier <x-icon name="chevron-down" class="size-4" />
                            </button>
                            <div x-cloak x-show="open" x-transition.opacity
                                 class="nav-menu absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
                                @foreach ($publishMenu as $item)
                                    <a href="{{ route($item['route']) }}" class="flex min-h-11 items-center gap-2 rounded-btn px-3 font-medium text-ink hover:bg-bg-alt">
                                        <x-icon :name="$item['icon']" class="size-4" /> {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Cloche (02, P1) : compteur ambre des non lues, 8 dernières, « Tout marquer comme lu ». --}}
                    <div class="relative hidden lg:block" x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false" data-bell>
                        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false"
                                aria-label="Notifications{{ $unreadCount ? ', '.$unreadCount.' non lue'.($unreadCount > 1 ? 's' : '') : '' }}"
                                class="relative inline-flex size-10 items-center justify-center rounded-btn text-ink-soft transition hover:bg-surface hover:text-ink">
                            <x-icon name="bell" class="size-5" />
                            @if ($unreadCount)
                                <span class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-secondary-400 px-1 text-xs font-semibold text-primary-950" aria-hidden="true">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </button>
                        <div x-cloak x-show="open" x-transition.opacity
                             class="nav-menu absolute right-0 mt-2 w-96 overflow-hidden rounded-2xl border border-line bg-surface shadow-card-hover">
                            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                                <p class="font-semibold text-ink">Notifications</p>
                                @if ($unreadCount)
                                    <form method="post" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">Tout marquer comme lu</button>
                                    </form>
                                @endif
                            </div>
                            @if ($latestNotifications->isEmpty())
                                <p class="px-4 py-6 text-sm text-ink-muted">Rien de neuf pour l'instant.</p>
                            @else
                                <ul class="max-h-96 overflow-y-auto p-1.5">
                                    @foreach ($latestNotifications as $notification)
                                        <li><x-notification-item :notification="$notification" /></li>
                                    @endforeach
                                </ul>
                            @endif
                            <a href="{{ route('notifications.index') }}" class="block border-t border-line px-4 py-3 text-center text-sm font-semibold text-primary-600 hover:bg-bg-alt dark:text-primary-300">Voir toutes les notifications</a>
                        </div>
                    </div>

                    <div class="relative hidden lg:block" x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false">
                        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false"
                                class="flex items-center gap-2 rounded-btn py-1 pl-1 pr-2 text-ink transition hover:bg-surface">
                            <x-avatar :user="$me" size="xs" />
                            <x-icon name="chevron-down" class="size-4 text-ink-muted" />
                            <span class="sr-only">Ouvrir le menu de ton compte</span>
                        </button>
                        <div x-cloak x-show="open" x-transition.opacity
                             class="nav-menu absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
                            <p class="truncate px-3 py-2 text-sm text-ink-muted">{{ $me->name }}</p>
                            @include('partials.account-links')
                        </div>
                    </div>
                @else
                    {{-- Le CTA unique du visiteur, mot pour mot, visible en permanence. Sauf sur l'inscription, où le bouton
                         du formulaire porte déjà ce libellé (pas de doublon) : la navigation y propose la connexion. --}}
                    @if (request()->routeIs('register'))
                        <a href="{{ route('login') }}" class="font-medium text-ink-muted transition hover:text-ink">Connexion</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden font-medium text-ink-muted transition hover:text-ink lg:inline">Connexion</a>
                        <x-button :href="route('register')" size="sm" class="max-lg:min-h-9 max-lg:px-3 max-lg:text-[13px]" data-nav-cta="true">{{ Copy::SIGNUP_CTA }}</x-button>
                    @endif
                @endauth
            </div>
        </header>
    </div>

    @isset($hero)
        {{ $hero }}
    @elseif ($pageHero)
        <x-list-hero :start="$pageHero['start']" :end="$pageHero['end']" :subtitle="$pageHero['subtitle']" :action="$pageHero['action'] ?? null" />
    @endisset

    <main id="contenu" @class(['mx-auto max-w-page px-4 sm:px-6', 'pb-16 sm:pb-24' => trim($slot) !== '' || session('status'), 'pt-8 sm:pt-14' => ! $hasHero])>
        @if (session('status'))
            <div role="status" class="mb-8 flex items-start gap-3 rounded-2xl border border-line bg-surface px-4 py-3 font-medium text-ink shadow-card">
                <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white dark:bg-secondary-400 dark:text-primary-950">
                    <x-icon name="check" class="size-4" />
                </span>
                <span class="pt-0.5">{{ session('status') }}</span>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Pied de page : grand texte défilant lisible (ink plein, partie italique en primary-600 ou secondary-400
         en sombre), puis l'accroche et les liens. Si l'utilisateur réduit les animations, le texte est arrêté
         et affiché en entier. --}}
    <footer class="overflow-hidden border-t border-line bg-bg-alt">
        <div class="py-10 sm:py-14">
            <p class="sr-only">Trouve des missions. Partage tes connaissances. Construis avec les tiens.</p>
            <div class="defile flex w-max whitespace-nowrap font-serif text-5xl font-medium tracking-tight text-ink sm:text-7xl motion-reduce:mx-auto motion-reduce:w-auto motion-reduce:max-w-page motion-reduce:flex-wrap motion-reduce:justify-center motion-reduce:px-4 motion-reduce:text-center motion-reduce:whitespace-normal motion-reduce:text-4xl motion-reduce:sm:text-6xl" aria-hidden="true" data-defile>
                @foreach (range(1, 2) as $copy)
                    <span @class(['flex gap-x-[0.3em] pr-[0.6em] motion-reduce:flex-wrap motion-reduce:justify-center motion-reduce:pr-0', 'motion-reduce:hidden' => $copy === 2])>
                        <span>Trouve des missions.</span>
                        <span>Partage tes connaissances.</span>
                        <span class="italic text-primary-600 dark:text-secondary-400">Construis avec les tiens.</span>
                    </span>
                @endforeach
            </div>
        </div>
        <div class="mx-auto grid max-w-page gap-8 border-t border-line px-4 py-10 sm:grid-cols-[2fr_1fr] sm:px-6">
            <div class="max-w-sm">
                <x-logo />
                <p class="mt-4 text-sm leading-relaxed text-ink-soft">{{ Brand::TAGLINE }}</p>
            </div>
            <nav aria-label="Rubriques" class="space-y-2 text-sm">
                <p class="font-semibold text-ink">Explorer</p>
                @foreach ($mainNav as $item)
                    <a href="{{ route($item['route']) }}" class="block py-1 text-ink-muted hover:text-ink">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>
        <div class="mx-auto flex max-w-page items-center justify-between gap-4 border-t border-line px-4 py-4 text-sm text-ink-muted sm:px-6">
            <p>© 2026 {{ Brand::NAME }}</p>
            <x-theme-toggle class="lg:hidden" />
        </div>
    </footer>

    {{-- Barre du bas mobile : flottante à 12 px du bas, opaque (aucun texte lisible au travers). --}}
    <nav class="fixed inset-x-3 bottom-3 z-40 lg:hidden" aria-label="Navigation mobile" style="margin-bottom: env(safe-area-inset-bottom)">
        <ul class="flex rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
            @foreach ($bottomNav as $item)
                @php($active = request()->routeIs($item['active']) && ! $meActive)
                <li class="min-w-0 flex-1">
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class([
                           'flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-medium transition',
                           'bg-bg-alt text-primary-600 ring-1 ring-secondary-400 dark:text-secondary-400' => $active,
                           'text-ink-muted' => ! $active,
                       ])>
                        <x-icon :name="$item['icon']" class="size-5" />
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
            <li class="relative min-w-0 flex-1" @auth x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false" @endauth>
                @auth
                    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" data-me-menu
                            aria-label="Moi{{ $unreadCount ? ', '.$unreadCount.' notification'.($unreadCount > 1 ? 's' : '').' non lue'.($unreadCount > 1 ? 's' : '') : '' }}"
                            @class([
                                'relative flex h-14 w-full flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-medium transition',
                                'bg-bg-alt text-primary-600 ring-1 ring-secondary-400 dark:text-secondary-400' => $meActive,
                                'text-ink-muted' => ! $meActive,
                            ])>
                        <x-icon name="user" class="size-5" />
                        Moi
                        @if ($unreadCount)
                            <span class="absolute right-3 top-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-secondary-400 px-1 text-xs font-semibold text-primary-950" aria-hidden="true">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity
                         class="nav-menu absolute bottom-full right-0 z-10 mb-3 w-64 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
                        <p class="truncate px-3 py-2 text-sm text-ink-muted">{{ $me->name }}</p>
                        @include('partials.account-links')
                    </div>
                @else
                    <a href="{{ route('login') }}" @if ($meActive) aria-current="page" @endif
                       @class([
                           'flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-medium transition',
                           'bg-bg-alt text-primary-600 ring-1 ring-secondary-400 dark:text-secondary-400' => $meActive,
                           'text-ink-muted' => ! $meActive,
                       ])>
                        <x-icon name="user" class="size-5" />
                        Moi
                    </a>
                @endauth
            </li>
        </ul>
    </nav>

    @if ($usesLivewire)
        @livewireScripts
    @endif
</body>
</html>
