@props(['points' => 0])

{{-- Cauris : icône suivie du chiffre (espace fine insécable pour les milliers). --}}
@php($formatted = number_format($points, 0, ',', "\u{202F}"))
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 text-sm font-semibold tabular-nums text-ink']) }}>
    <x-cauri />
    {{ $formatted }}
    <span class="sr-only">{{ $points > 1 ? 'cauris' : 'cauri' }}</span>
</span>
