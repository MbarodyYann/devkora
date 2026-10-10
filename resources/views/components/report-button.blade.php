@props(['content', 'type'])

{{-- Signaler (02, P1) : pour un membre, jamais sur son propre contenu. Menu sans JavaScript (<details>). --}}
@auth
    @if (auth()->id() !== $content->user_id && $content->hidden_at === null)
        <details {{ $attributes->merge(['class' => 'group relative']) }} data-report>
            <summary class="inline-flex min-h-9 cursor-pointer list-none items-center gap-1.5 rounded-btn px-2 text-sm text-ink-muted hover:text-ink [&::-webkit-details-marker]:hidden">
                <x-icon name="flag" class="size-4" /> Signaler
            </summary>
            <form method="post" action="{{ route('reports.store') }}" class="nav-menu z-50 space-y-1 rounded-2xl border border-line bg-surface p-3 shadow-card-hover max-sm:fixed max-sm:inset-x-3 max-sm:bottom-24 sm:absolute sm:right-0 sm:mt-1 sm:w-64">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="id" value="{{ $content->id }}">
                <p class="px-1 pb-1 text-sm font-medium text-ink">Pourquoi signaler ?</p>
                @foreach (App\Enums\ReportReason::cases() as $reason)
                    <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-btn px-1 text-sm text-ink-soft hover:bg-bg-alt">
                        <input type="radio" name="reason" value="{{ $reason->value }}" required class="size-4 accent-primary-600"> {{ $reason->label() }}
                    </label>
                @endforeach
                <x-button type="submit" variant="secondary" size="sm" class="mt-2 w-full">Signaler ce contenu</x-button>
                <p class="px-1 pt-1 text-xs text-ink-muted">Un modérateur de la communauté le regardera.</p>
            </form>
        </details>
    @endif
@endauth
