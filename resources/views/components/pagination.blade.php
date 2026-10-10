{{-- Pagination des listes Livewire. --}}
@if ($paginator->hasPages())
    @php($pageButton = 'btn-outline inline-flex min-h-10 items-center gap-1 rounded-btn px-3.5 text-sm font-semibold')
    <nav aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-ink-muted">{{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }}</p>

        <div class="flex items-center gap-3">
            @if ($paginator->onFirstPage())
                <span class="{{ $pageButton }} pointer-events-none opacity-40" aria-disabled="true">
                    <x-icon name="arrow-left" class="size-4" /> Précédents
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="{{ $pageButton }}">
                    <x-icon name="arrow-left" class="size-4" /> Précédents
                </button>
            @endif

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="{{ $pageButton }}">
                    Suivants <x-icon name="arrow-right" class="size-4" />
                </button>
            @else
                <span class="{{ $pageButton }} pointer-events-none opacity-40" aria-disabled="true">
                    Suivants <x-icon name="arrow-right" class="size-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
