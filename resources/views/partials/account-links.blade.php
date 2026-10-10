{{-- Liens du compte (menu de l'avatar sur ordinateur, menu « Moi » sur mobile). --}}
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
