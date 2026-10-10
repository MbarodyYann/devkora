@props(['amount', 'size' => 'md'])

{{-- Budget d'une mission (08) : chiffre en Newsreader, « FCFA » à côté. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-1.5 whitespace-nowrap text-ink']) }}>
    <span @class(['font-serif font-medium leading-none tracking-tight tabular-nums', 'text-[28px]' => $size === 'md', 'text-[44px] sm:text-[56px]' => $size === 'lg'])>{{ App\Support\Money::format($amount) }}</span>
    <span class="text-sm font-semibold text-ink-muted">FCFA</span>
</span>
