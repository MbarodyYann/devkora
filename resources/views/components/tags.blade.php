@props(['skills', 'route' => 'home'])

{{-- Tags d'une question ou d'un article : liens vers la liste filtrée, tous en gris neutre. --}}
@if ($skills->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap gap-1.5']) }}>
        @foreach ($skills as $skill)
            <li class="min-w-0 max-w-full">
                <a href="{{ route($route, ['tag' => $skill->slug]) }}" class="inline-flex max-w-full rounded-[6px] bg-bg-alt px-2.5 py-1 text-sm text-ink-soft ring-1 ring-line transition hover:text-ink"><span class="sr-only">{{ $route === 'articles.index' ? 'Articles' : 'Questions' }} sur </span><span class="truncate">{{ $skill->name }}</span></a>
            </li>
        @endforeach
    </ul>
@endif
