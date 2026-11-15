{{-- Cadre « repères » : bordure fine et petits carrés aux 4 coins, en gris. Un seul par page. --}}
<div {{ $attributes->merge(['class' => 'relative border border-line']) }}>
    @foreach (['-left-1 -top-1', '-right-1 -top-1', '-bottom-1 -left-1', '-bottom-1 -right-1'] as $corner)
        <span class="absolute {{ $corner }} size-2 bg-ink-muted" aria-hidden="true"></span>
    @endforeach
    {{ $slot }}
</div>
