@props(['href' => null, 'padding' => 'p-5 sm:p-8'])

{{-- Carte : fond surface, bordure fine, coins de 24 px (16 px sur mobile), ombre très douce.
     Cliquable : l'ombre s'accentue et la carte monte de 2 px. --}}
@php
    $classes = "block min-w-0 rounded-2xl border border-line bg-surface shadow-card sm:rounded-card $padding"
        .($href ? ' transition duration-200 hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:hover:translate-y-0' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
@endif
