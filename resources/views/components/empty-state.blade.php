@props(['title'])

{{-- État vide : une invitation à agir, jamais un constat sec (11, « Règles d'écriture »). --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-line px-6 py-12 text-center']) }}>
    <x-cauri class="mx-auto mb-4 size-8 text-ink-muted" />
    <p class="font-serif text-2xl font-medium text-ink">{{ $title }}</p>
    @if (trim($slot))
        <div class="mx-auto mt-3 max-w-md text-ink-soft">{{ $slot }}</div>
    @endif
</div>
