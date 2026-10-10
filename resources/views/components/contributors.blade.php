@props(['contributors', 'compact' => false])

{{-- Contributeurs du mois : rang, avatar, nom, ville, cauris gagnés sur 30 jours. --}}
<ol {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5']) }} data-contributors>
    @foreach ($contributors as $contributor)
        <li class="min-w-0">
            <a href="{{ route('devs.show', $contributor) }}" class="flex h-full min-w-0 items-center gap-3 rounded-2xl border border-line bg-surface p-4 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover lg:flex-col lg:text-center">
                <span class="relative shrink-0">
                    <x-avatar :user="$contributor" :size="$compact ? 'sm' : 'md'" />
                    <span class="absolute -right-1 -top-1 inline-flex size-6 items-center justify-center rounded-full bg-secondary-400 text-xs font-semibold text-primary-950" aria-label="Rang {{ $loop->iteration }}">{{ $loop->iteration }}</span>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium text-ink">{{ $contributor->name }}</span>
                    <span class="block truncate text-sm text-ink-muted">{{ $contributor->profile?->city }} <span aria-hidden="true">{{ $contributor->profile?->flag() }}</span></span>
                    <span class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-ink"><x-cauri /> +{{ $contributor->month_points }} <span class="font-normal text-ink-muted">ce mois-ci</span></span>
                </span>
            </a>
        </li>
    @endforeach
</ol>
