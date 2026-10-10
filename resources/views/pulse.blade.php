@php
    use App\Support\Money;
    $maxDevs = max(1, collect($devsByCountry)->max('count'));
    $maxDemand = max(1, collect($demanded)->max('count'));
@endphp

<x-layouts.app title="Le Pouls" :page-hero="[
    'start' => 'Combien facturer ?',
    'end' => 'Le marché te répond.',
    'subtitle' => 'Tarifs médians en FCFA, compétences recherchées et activité de la communauté, calculés à partir des échanges réels.',
]">
    {{-- Activité des 30 derniers jours, à cheval sur l'en-tête --}}
    <x-card padding="p-4 sm:p-6" class="relative z-10 -mt-6 sm:-mt-10">
        <h2 class="text-xl sm:text-2xl">Les 30 derniers jours</h2>
        <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4" data-activity>
            @foreach ([['resolved', 'Questions résolues'], ['articles', 'Articles publiés'], ['teams', 'Équipes formées'], ['missions', 'Missions terminées']] as [$key, $label])
                <div class="min-w-0">
                    <dt class="text-[11px] font-semibold uppercase tracking-[.14em] text-ink-muted">{{ $label }}</dt>
                    <dd class="mt-1 font-serif text-[40px] font-medium leading-none text-ink">{{ $activity[$key] }}</dd>
                </div>
            @endforeach
        </dl>
    </x-card>

    {{-- Combien facturer ? --}}
    <section class="mt-12" aria-labelledby="tarifs-titre">
        <h2 id="tarifs-titre" class="text-[32px] sm:text-[44px]">Les tarifs médians, compétence par compétence</h2>
        <p class="mt-3 max-w-2xl text-ink-soft">Médiane des budgets des missions entre devs et des prix proposés, par compétence. La moitié des montants se situe dans la fourchette indiquée. Seules les compétences avec 3 montants au moins apparaissent.</p>
        @if ($rates === [])
            <x-empty-state title="Pas encore assez de missions pour un tarif fiable." class="mt-6">Les tarifs apparaissent dès 3 montants pour une compétence.</x-empty-state>
        @else
            <x-card padding="p-0" class="mt-6 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[34rem] text-left text-sm" data-rates>
                        <thead class="bg-bg-alt text-ink-muted">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium">Compétence</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Médiane</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Fourchette</th>
                                <th scope="col" class="px-5 py-3 text-right font-medium">Montants</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($rates as $rate)
                                <tr>
                                    <th scope="row" class="px-5 py-3 font-medium text-ink">
                                        <span class="flex items-center gap-2"><x-icon name="check" class="size-4 shrink-0 text-primary-600 dark:text-secondary-400" /> {{ $rate['skill'] }}</span>
                                    </th>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-serif text-lg text-ink">{{ Money::fcfa($rate['median']) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-ink-soft">{{ Money::format($rate['low']) }} à {{ Money::fcfa($rate['high']) }}</td>
                                    <td class="px-5 py-3 text-right text-ink-muted">{{ $rate['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    </section>

    <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Compétences les plus demandées --}}
        <section aria-labelledby="demande-titre">
            <x-card class="h-full">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 id="demande-titre" class="text-[28px] sm:text-[32px]">Les compétences demandées</h2>
                    <form method="get" action="{{ route('pulse') }}" class="min-w-0" x-data>
                        <label for="pays" class="sr-only">Pays</label>
                        <x-select name="pays" :options="$countries" :value="$country" placeholder="Tous les pays" x-on:change="$el.form.submit()" class="min-h-10! text-sm" />
                        <noscript><x-button type="submit" variant="secondary" size="sm" class="mt-2">Afficher</x-button></noscript>
                    </form>
                </div>
                <p class="mt-2 text-sm text-ink-muted">Dans les annonces et les questions{{ $country ? ', '.App\Support\Countries::name($country) : '' }}.</p>
                @if ($demanded === [])
                    <p class="mt-6 text-ink-muted">Pas encore de demande dans ce pays.</p>
                @else
                    <ol class="mt-5 space-y-3" data-demanded>
                        @foreach ($demanded as $item)
                            <li>
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <a href="{{ route('devs.index', ['competence' => $item['slug']]) }}" class="font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">{{ $item['skill'] }}</a>
                                    <span class="text-ink-muted">{{ $item['count'] }}</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-bg-alt ring-1 ring-line" aria-hidden="true">
                                    <div class="h-full rounded-full bg-primary-600 dark:bg-secondary-400" style="width: {{ round($item['count'] / $maxDemand * 100) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-card>
        </section>

        {{-- Devs par pays : barres en CSS --}}
        <section aria-labelledby="pays-titre">
            <x-card class="h-full">
                <h2 id="pays-titre" class="text-[28px] sm:text-[32px]">Les devs, pays par pays</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ collect($devsByCountry)->sum('count') }} devs dans {{ count($devsByCountry) }} pays.</p>
                <ol class="mt-5 space-y-3" data-devs-by-country>
                    @foreach ($devsByCountry as $row)
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <a href="{{ route('devs.index', ['pays' => $row['country']]) }}" class="font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300"><span aria-hidden="true">{{ $row['flag'] }}</span> {{ $row['name'] }}</a>
                                <span class="text-ink-muted">{{ $row['count'] }}</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-bg-alt ring-1 ring-line" aria-hidden="true">
                                <div class="h-full rounded-full bg-secondary-400" style="width: {{ round($row['count'] / $maxDevs * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </section>
    </div>
</x-layouts.app>
