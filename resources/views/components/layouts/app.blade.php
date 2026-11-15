@props(['title' => null, 'description' => null, 'pageHero' => null, 'noindex' => false, 'ogImage' => null, 'ogType' => 'website', 'welcome' => false])

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
    // Bandeau indigo sous la navigation : hero d'une page (slot) ou en-tête compact d'une page de liste.
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
    {{-- Adresse canonique (sans les filtres) et partage : Open Graph et Twitter. --}}
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ Brand::NAME }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/og-default.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ Brand::NAME }}, le point de rencontre des devs africains">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('images/og-default.png') }}">
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
        <p role="status" data-demo-banner class="border-b border-line bg-bg-alt px-4 py-2 text-center text-sm text-ink-soft">
            Tu explores DevKora avec le compte de démonstration de {{ $demoPersona }}.
        </p>
    @endif

    {{-- Bandeau du visiteur (accueil) : une ligne, qu'on peut fermer ; le choix est gardé dans le navigateur.
         Pas de bouton d'inscription ici : celui de la navigation, juste en dessous, suffit (pas de doublon). --}}
    @if ($welcome && ! $me)
        <div data-welcome class="border-b border-line bg-bg-alt"
             x-data="{
                 open: true,
                 init() { try { this.open = localStorage.getItem('welcome') !== 'closed' } catch (e) {} },
                 close() { this.open = false; try { localStorage.setItem('welcome', 'closed') } catch (e) {} },
             }"
             x-show="open">
            <div class="mx-auto flex max-w-page items-center gap-x-4 gap-y-1 px-4 py-2 text-sm max-sm:flex-wrap sm:px-6 lg:justify-center">
                <p class="min-w-0 text-ink-soft max-sm:basis-full"><strong class="font-semibold text-ink">Nouveau ici ?</strong> DevKora, c'est le point de rencontre des devs africains.</p>
                <a href="{{ route('about') }}" class="inline-flex min-h-9 shrink-0 items-center font-semibold text-primary-600 underline underline-offset-4 dark:text-primary-300">Découvrir</a>
                <button type="button" x-cloak aria-label="Fermer ce message"
                        x-on:click="close()"
                        class="-mr-2 ml-auto inline-flex size-9 shrink-0 items-center justify-center rounded-btn text-ink-muted transition hover:text-ink lg:ml-0">
                    <x-icon name="x" class="size-4" />
                </button>
            </div>
        </div>
    @endif

    {{-- Navigation : une barre pleine largeur, opaque, collée en haut. Ni effet flottant, ni transparence. --}}
    <div class="sticky top-0 z-40 border-b border-line bg-bg">
        <header class="mx-auto flex h-14 max-w-page items-center gap-2 bg-bg px-4 sm:h-16 sm:gap-3 sm:px-6 lg:gap-4 xl:gap-6">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ Brand::NAME }}, accueil">
                <x-logo class="max-[379px]:text-[22px]" />
            </a>

            <nav class="hidden flex-1 items-center justify-center gap-1 lg:flex" aria-label="Navigation principale">
                @foreach ($mainNav as $item)
                    @php($active = request()->routeIs($item['active']))
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class([
                           // Entre 1 024 et 1 280 px, les rubriques se resserrent pour que le bouton de droite reste dans la barre.
                           'relative rounded-btn px-2 py-2 font-medium transition xl:px-3',
                           'hidden xl:inline-block' => $item['wide'] ?? false,
                           'bg-bg-alt text-ink' => $active,
                           'text-ink-muted hover:text-ink' => ! $active,
                       ])>
                        {{ $item['label'] }}
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
                                 class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
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
                                <span class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-primary-600 px-1 text-xs font-semibold text-white" aria-hidden="true">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </button>
                        <div x-cloak x-show="open" x-transition.opacity
                             class="absolute right-0 mt-2 w-96 overflow-hidden rounded-2xl border border-line bg-surface shadow-card-hover">
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
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
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
                <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white">
                    <x-icon name="check" class="size-4" />
                </span>
                <span class="pt-0.5">{{ session('status') }}</span>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Pied de page : l'accroche, les rubriques et les liens de DevKora. --}}
    <footer class="border-t border-line bg-bg-alt">
        <div class="mx-auto grid max-w-page gap-8 px-4 py-10 sm:grid-cols-[2fr_1fr_1fr] sm:px-6">
            <div class="max-w-sm">
                <x-logo />
                <p class="mt-4 text-sm leading-relaxed text-ink-soft">{{ Brand::TAGLINE }}</p>
            </div>
            <nav aria-label="Rubriques" class="space-y-2 text-sm">
                <p class="font-semibold text-ink">Explorer</p>
                @foreach ($mainNav as $item)
                    {{-- À propos est déjà dans la colonne « DevKora ». --}}
                    @continue($item['route'] === 'about')
                    <a href="{{ route($item['route']) }}" class="block py-1 text-ink-muted hover:text-ink">{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <nav aria-label="DevKora" class="space-y-2 text-sm">
                <p class="font-semibold text-ink">DevKora</p>
                <a href="{{ route('about') }}" class="block py-1 text-ink-muted hover:text-ink">À propos</a>
                <a href="{{ route('privileges') }}" class="block py-1 text-ink-muted hover:text-ink">Privilèges</a>
            </nav>
        </div>
        <div class="mx-auto flex max-w-page items-center justify-between gap-4 border-t border-line px-4 py-4 text-sm text-ink-muted sm:px-6">
            <p>© 2026 {{ Brand::NAME }}</p>
            <x-theme-toggle class="lg:hidden" />
        </div>
    </footer>

    {{-- Barre du bas mobile : opaque (aucun texte lisible au travers). --}}
    <nav class="fixed inset-x-3 bottom-3 z-40 lg:hidden" aria-label="Navigation mobile" style="margin-bottom: env(safe-area-inset-bottom)">
        <ul class="flex rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
            @foreach ($bottomNav as $item)
                @php($active = request()->routeIs($item['active']) && ! $meActive)
                <li class="min-w-0 flex-1">
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class([
                           'flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-medium transition',
                           'bg-bg-alt text-ink' => $active,
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
                                'bg-bg-alt text-ink' => $meActive,
                                'text-ink-muted' => ! $meActive,
                            ])>
                        <x-icon name="user" class="size-5" />
                        Moi
                        @if ($unreadCount)
                            <span class="absolute right-3 top-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-primary-600 px-1 text-xs font-semibold text-white" aria-hidden="true">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity
                         class="absolute bottom-full right-0 z-10 mb-3 w-64 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-card-hover">
                        <p class="truncate px-3 py-2 text-sm text-ink-muted">{{ $me->name }}</p>
                        @include('partials.account-links')
                    </div>
                @else
                    <a href="{{ route('login') }}" @if ($meActive) aria-current="page" @endif
                       @class([
                           'flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-xs font-medium transition',
                           'bg-bg-alt text-ink' => $meActive,
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
