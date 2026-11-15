@props(['tone' => 'info'])

{{-- Étiquette texte seul : aucun point ni tiret devant, en gris neutre. Le sens est porté par le mot.
     Seul le ton « done » (Terminée, Acceptée, Résolue) se distingue : aplat indigo et coche.
     « muted » (Indisponible, Close) est plus effacé. --}}
@php
    $classes = match ($tone) {
        'done' => 'bg-primary-600 text-white',
        'muted' => 'bg-bg-alt text-ink-muted ring-1 ring-line',
        default => 'bg-bg-alt text-ink-soft ring-1 ring-line',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex max-w-full items-center gap-1 rounded-[6px] px-2.5 py-1 text-sm font-medium $classes"]) }}>
    @if ($tone === 'done')
        <x-icon name="check" class="size-3.5 shrink-0" />
    @endif
    <span class="flex min-w-0 items-center gap-1 overflow-hidden text-ellipsis whitespace-nowrap">{{ $slot }}</span>
</span>
