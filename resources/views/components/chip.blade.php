@props(['tone' => 'info'])

{{-- Étiquette texte seul (08, règle 3) : aucun point ni tiret devant. Tons :
     info (Ouvert aux collaborations, Ouverte) · warm (En cours, Nouveau) · done (Terminée, Acceptée : coche)
     · muted (Indisponible, Close) · neutral (compétences). L'ambre n'est jamais du texte sur fond clair. --}}
@php
    $classes = match ($tone) {
        'warm' => 'bg-secondary-100 text-secondary-800 dark:bg-secondary-950 dark:text-secondary-300',
        'done' => 'bg-primary-600 text-white dark:bg-secondary-400 dark:text-primary-950',
        'muted' => 'bg-bg-alt text-ink-muted ring-1 ring-line',
        'neutral' => 'bg-bg-alt text-ink-soft ring-1 ring-line',
        default => 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex max-w-full items-center gap-1 rounded-[6px] px-2.5 py-1 text-sm font-medium $classes"]) }}>
    @if ($tone === 'done')
        <x-icon name="check" class="size-3.5 shrink-0" />
    @endif
    <span class="flex min-w-0 items-center gap-1 overflow-hidden text-ellipsis whitespace-nowrap">{{ $slot }}</span>
</span>
