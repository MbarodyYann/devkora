{{-- Vote : flèches et score. État pressé (aria-pressed), message de refus lisible sous le score. --}}
<div class="relative flex shrink-0 items-center gap-2 max-sm:order-last sm:w-12 sm:flex-col sm:gap-1" data-vote>
    <button type="button" wire:click="up" wire:loading.attr="disabled" aria-pressed="{{ $myVote === 1 ? 'true' : 'false' }}"
            aria-label="Voter pour {{ $label }}"
            @class([
                'inline-flex size-10 items-center justify-center rounded-btn border transition',
                'border-secondary-400 bg-secondary-400 text-primary-950' => $myVote === 1,
                'border-secondary-400 text-ink hover:bg-secondary-400/15' => $myVote !== 1,
            ])>
        <x-icon name="chevron-up" class="size-5" />
    </button>
    <span class="font-serif text-xl font-medium tabular-nums text-ink" aria-live="polite">
        {{ $score }}<span class="sr-only"> {{ abs($score) > 1 ? 'votes' : 'vote' }}</span>
    </span>
    <button type="button" wire:click="down" wire:loading.attr="disabled" aria-pressed="{{ $myVote === -1 ? 'true' : 'false' }}"
            aria-label="Voter contre {{ $label }}"
            @class([
                'inline-flex size-10 items-center justify-center rounded-btn border transition',
                'border-secondary-400 bg-secondary-400 text-primary-950' => $myVote === -1,
                'border-secondary-400 text-ink hover:bg-secondary-400/15' => $myVote !== -1,
            ])>
        <x-icon name="chevron-down" class="size-5" />
    </button>
    @if ($message)
        <p role="alert" class="absolute left-0 z-10 w-52 max-sm:bottom-full max-sm:mb-2 sm:top-full sm:mt-2 rounded-field border border-secondary-500 bg-surface p-2.5 text-sm text-secondary-800 shadow-card dark:text-secondary-300">{{ $message }}</p>
    @endif
</div>
