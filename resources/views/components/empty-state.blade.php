@props(['title'])

{{-- État vide : une invitation à agir, jamais un constat sec (11, « Règles d'écriture »). --}}
<div {{ $attributes->merge(['class' => 'dots rounded-2xl border border-dashed border-line px-6 py-14 text-center sm:rounded-card']) }}>
    <x-cauri class="mx-auto mb-5 size-10 text-secondary-400" />
    <p class="font-serif text-2xl font-medium tracking-tight text-ink sm:text-[28px]">{{ $title }}</p>
    @if (trim($slot))
        <div class="mx-auto mt-3 max-w-md text-ink-soft">{{ $slot }}</div>
    @endif
</div>
