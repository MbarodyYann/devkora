@props(['skills', 'route' => 'questions.index'])

{{-- Tags d'une question ou d'un article : liens vers la liste filtrée ; les tags locaux (Wave, USSD…) sont mis en valeur. --}}
@if ($skills->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap gap-1.5']) }}>
        @foreach ($skills as $skill)
            <li class="min-w-0 max-w-full">
                <a href="{{ route($route, ['tag' => $skill->slug]) }}" @class([
                    'inline-flex max-w-full rounded-[6px] px-2.5 py-1 text-sm font-medium transition',
                    'bg-secondary-100 text-secondary-800 hover:bg-secondary-200 dark:bg-secondary-950 dark:text-secondary-300' => $skill->is_local,
                    'bg-bg-alt text-ink-soft ring-1 ring-line hover:text-primary-600 dark:hover:text-primary-200' => ! $skill->is_local,
                ])><span class="sr-only">{{ $route === 'articles.index' ? 'Articles' : 'Questions' }} sur </span><span class="truncate">{{ $skill->name }}</span></a>
            </li>
        @endforeach
    </ul>
@endif
