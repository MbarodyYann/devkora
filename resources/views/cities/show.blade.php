@php($name = $city['name'])

<x-layouts.app :title="'Les devs de '.$name" :description="'Les devs de '.$name.' sur DevKora : profils, événements, annonces et questions de la communauté locale.'" :page-hero="[
    'start' => 'Les devs de '.$name.',',
    'end' => 'à deux pas de chez toi.',
    'subtitle' => $devCount.' '.($devCount > 1 ? 'devs inscrits' : 'dev inscrit').' à '.$name.' : rencontre-les aux événements, réponds à leurs questions, construis avec eux.',
]">
    <x-card padding="p-4 sm:p-5" class="relative z-10 -mt-6 sm:-mt-10">
        <p class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink">
            <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="size-4 text-ink-muted" /> {{ $name }}, {{ App\Support\Countries::name($city['country']) }} <span aria-hidden="true">{{ App\Support\Countries::flag($city['country']) }}</span></span>
            <a href="{{ route('devs.index', ['pays' => $city['country']]) }}" class="font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">Tous les devs du pays →</a>
        </p>
    </x-card>

    @if ($events->isNotEmpty())
        <section class="mt-10" aria-labelledby="ville-evenements">
            <h2 id="ville-evenements" class="text-[28px] sm:text-[32px]">Les prochains événements à {{ $name }}</h2>
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <li class="min-w-0"><x-event-card :event="$event" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-10" aria-labelledby="ville-devs">
        <h2 id="ville-devs" class="text-[28px] sm:text-[32px]">Ses devs</h2>
        @if ($devs->isEmpty())
            <p class="mt-3 text-ink-muted">Personne n'est encore inscrit à {{ $name }}. Les événements, eux, sont ouverts à tous.</p>
        @else
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($devs as $dev)
                    <li class="min-w-0"><x-dev-card :dev="$dev" /></li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($listings->isNotEmpty())
        <section class="mt-10" aria-labelledby="ville-annonces">
            <h2 id="ville-annonces" class="text-[28px] sm:text-[32px]">Ils cherchent du renfort</h2>
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($listings as $listing)
                    <li class="min-w-0"><x-listing-card :listing="$listing" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($questions->isNotEmpty())
        <section class="mt-10" aria-labelledby="ville-questions">
            <h2 id="ville-questions" class="text-[28px] sm:text-[32px]">Leurs dernières questions</h2>
            <ul class="mt-4 space-y-4">
                @foreach ($questions as $question)
                    <li><x-question-card :question="$question" /></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
