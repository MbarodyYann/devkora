{{-- Liens du compte (menu de l'avatar sur ordinateur, menu « Moi » sur mobile). --}}
<a href="{{ route('notifications.index') }}" class="flex min-h-11 items-center justify-between gap-2 rounded-btn px-3 font-medium text-ink hover:bg-bg-alt lg:hidden">
    <span class="flex items-center gap-2"><x-icon name="bell" class="size-4" /> Notifications</span>
    @if ($unreadCount)
        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-secondary-400 px-1.5 text-xs font-semibold text-primary-950">{{ $unreadCount }}</span>
    @endif
</a>
{{-- Sur mobile, les rubriques absentes de la barre du bas. --}}
<a href="{{ route('events.index') }}" class="flex min-h-11 items-center gap-2 rounded-btn px-3 font-medium text-ink hover:bg-bg-alt lg:hidden">
    <x-icon name="calendar" class="size-4" /> Événements
</a>
@can('moderate')
    <a href="{{ route('moderation.index') }}" class="flex min-h-11 items-center gap-2 rounded-btn px-3 font-medium text-ink hover:bg-bg-alt">
        <x-icon name="shield" class="size-4" /> Modération
    </a>
@endcan
<a href="{{ route('devs.show', $me) }}" class="flex min-h-11 items-center gap-2 rounded-btn px-3 font-medium text-ink hover:bg-bg-alt">
    <x-icon name="user" class="size-4" /> Mon profil
</a>
<a href="{{ route('profile.edit', $me) }}" class="flex min-h-11 items-center gap-2 rounded-btn px-3 font-medium text-ink hover:bg-bg-alt">
    <x-icon name="pencil" class="size-4" /> Modifier mon profil
</a>
<form method="post" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="flex min-h-11 w-full items-center gap-2 rounded-btn px-3 text-left font-medium text-ink hover:bg-bg-alt">
        <x-icon name="log-out" class="size-4" /> Me déconnecter
    </button>
</form>
