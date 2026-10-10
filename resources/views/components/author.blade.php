@props(['user', 'date', 'verb' => null])

{{-- Auteur d'un contenu : avatar, nom, étiquette « Nouveau », date (« 12 oct. 2026 »). --}}
<div {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-muted']) }}>
    <a href="{{ route('devs.show', $user) }}" class="flex min-w-0 items-center gap-2 font-medium text-ink hover:text-primary-600 dark:hover:text-primary-300">
        <x-avatar :user="$user" size="xs" />
        <span class="truncate">{{ $user->name }}</span>
    </a>
    <x-new-badge :user="$user" class="py-0.5! text-xs!" />
    <span>{{ $verb ? $verb.' le ' : '' }}<time datetime="{{ $date->toIso8601String() }}">{{ $date->translatedFormat('j M Y') }}</time></span>
</div>
