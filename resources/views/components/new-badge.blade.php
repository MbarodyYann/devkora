@props(['user'])

{{-- « Nouveau » : membre inscrit depuis moins de 14 jours (02, « Bienveillance »). --}}
@if ($user->isNewMember())
    <x-chip tone="warm" title="Inscrit depuis moins de 14 jours" {{ $attributes }}>Nouveau</x-chip>
@endif
