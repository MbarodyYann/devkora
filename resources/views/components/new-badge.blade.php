@props(['user'])

{{-- « Nouveau » : membre inscrit depuis peu (config devkora.new_member_days, 02 « Bienveillance »). --}}
@if ($user->isNewMember())
    <x-chip tone="warm" title="Inscrit depuis moins de {{ config('devkora.new_member_days') }} jours" {{ $attributes }}>Nouveau</x-chip>
@endif
