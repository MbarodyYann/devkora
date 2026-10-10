{{-- Vote « Utile » : bouton ambre en contour, en aplat une fois voté (aria-pressed). --}}
<div class="relative" data-useful>
    <button type="button" wire:click="toggle" wire:loading.attr="disabled" aria-pressed="{{ $voted ? 'true' : 'false' }}"
            @class([
                'inline-flex min-h-11 items-center gap-2 rounded-btn px-4 text-[15px] font-semibold',
                'btn-amber' => $voted,
                'btn-outline' => ! $voted,
            ])>
        <x-icon name="check" class="size-4" /> Utile <span class="tabular-nums">({{ $score }})</span>
    </button>
    @if ($message)
        <p role="alert" class="absolute left-0 top-full z-10 mt-2 w-60 rounded-field border border-secondary-500 bg-surface p-2.5 text-sm text-secondary-800 shadow-card dark:text-secondary-300">{{ $message }}</p>
    @endif
</div>
