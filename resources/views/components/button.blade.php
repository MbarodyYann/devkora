@props(['variant' => 'primary', 'href' => null, 'type' => 'button', 'size' => 'md'])

{{-- Tous les boutons sont ambre (08, règle 4) : primary en aplat, secondary en contour.
     ghost : lien texte discret (action secondaire, 11 règle 3). --}}
@php
    // Un « hidden » passé en classe (ex. « hidden sm:inline-flex ») l'emporte sur l'affichage par défaut.
    $display = preg_match('/(^|\s)hidden(\s|$)/', (string) $attributes->get('class')) ? '' : 'inline-flex ';
    $classes = $display.'items-center justify-center gap-2 rounded-btn font-semibold whitespace-nowrap disabled:cursor-not-allowed '
        .match ($size) {
            'xs' => 'min-h-9 px-3 text-[13px] ',
            'sm' => 'min-h-10 px-3.5 text-sm ',
            'lg' => 'min-h-12 px-6 text-base ',
            default => 'min-h-11 px-4 text-[15px] ',
        }
        .match ($variant) {
            'primary' => 'btn-amber',
            'secondary' => 'btn-outline',
            default => 'text-primary-600 underline-offset-4 hover:underline dark:text-primary-300',
        };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
