@props(['user', 'date', 'verb' => null, 'relative' => false, 'country' => false])

{{-- Auteur d'un contenu : avatar, nom, étiquette « Nouveau », date (« 12 oct. 2026 »).
     relative : date relative (« il y a 3 h »), la date exacte en infobulle. country : drapeau et nom du pays. --}}
<div {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-muted']) }}>
    <a href="{{ route('devs.show', $user) }}" class="flex min-w-0 items-center gap-2 font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">
        <x-avatar :user="$user" size="xs" />
        <span class="truncate">{{ $user->name }}</span>
    </a>
    @if ($country && $user->profile?->country)
        <span title="{{ App\Support\Countries::name($user->profile->country) }}"><span aria-hidden="true">{{ App\Support\Countries::flag($user->profile->country) }}</span><span class="sr-only">{{ App\Support\Countries::name($user->profile->country) }}</span></span>
    @endif
    <x-new-badge :user="$user" class="py-0.5! text-xs!" />
    @if ($relative)
        <span><time datetime="{{ $date->toIso8601String() }}" title="{{ $date->translatedFormat('j M Y à H:i') }}">{{ $date->diffForHumans(short: true) }}</time></span>
    @else
        <span>{{ $verb ? $verb.' le ' : '' }}<time datetime="{{ $date->toIso8601String() }}">{{ $date->translatedFormat('j M Y') }}</time></span>
    @endif
</div>
