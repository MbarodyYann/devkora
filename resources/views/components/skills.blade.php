@props(['skills', 'limit' => null, 'link' => true])

{{-- Compétences en étiquettes neutres ; liées à l'annuaire filtré sur la page de profil. --}}
@php
    $visible = $limit ? $skills->take($limit) : $skills;
    $rest = $skills->count() - $visible->count();
@endphp

@if ($skills->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap gap-1.5']) }}>
        @foreach ($visible as $skill)
            <li class="min-w-0 max-w-full">
                @if ($link)
                    <a href="{{ route('devs.index', ['competence' => $skill->slug]) }}"
                       class="inline-flex max-w-full rounded-[6px] bg-bg-alt px-2.5 py-1 text-sm font-medium text-ink-soft ring-1 ring-line transition hover:text-primary-600 hover:ring-primary-300 dark:hover:text-primary-200 dark:hover:ring-primary-700"><span class="sr-only">Devs en </span><span class="truncate">{{ $skill->name }}</span></a>
                @else
                    <x-chip tone="neutral">{{ $skill->name }}</x-chip>
                @endif
            </li>
        @endforeach
        @if ($rest > 0)
            <li><x-chip tone="neutral">+{{ $rest }}</x-chip></li>
        @endif
    </ul>
@endif
