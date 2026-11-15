{{-- Cadre « repères » : bordure fine et petits carrés aux 4 coins, en secondary-400. Un seul par page. --}}
<div {{ $attributes->merge(['class' => 'relative border border-secondary-400']) }}>
    @foreach (['-left-1 -top-1', '-right-1 -top-1', '-bottom-1 -left-1', '-bottom-1 -right-1'] as $corner)
        <span class="absolute {{ $corner }} size-2 bg-secondary-400" aria-hidden="true"></span>
    @endforeach
    {{ $slot }}
</div>
