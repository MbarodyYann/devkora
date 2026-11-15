<x-layouts.app title="Notifications" :noindex="true">
    <div class="mx-auto max-w-2xl">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-[38px] sm:text-5xl">Ce qui s'est passé pour toi.</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm">Tout marquer comme lu</x-button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <x-empty-state title="Rien de neuf pour l'instant." class="mt-8">
                Réponds à une question ou propose ton aide sur une annonce : les réponses arriveront ici.
            </x-empty-state>
        @else
            <x-card padding="p-2 sm:p-3" class="mt-8">
                <ul class="divide-y divide-line">
                    @foreach ($notifications as $notification)
                        <li><x-notification-item :notification="$notification" /></li>
                    @endforeach
                </ul>
            </x-card>
            @if ($notifications->hasPages())
                <div class="mt-6 flex justify-between gap-3">
                    @if ($notifications->previousPageUrl())<x-button :href="$notifications->previousPageUrl()" variant="secondary" size="sm">Plus récentes</x-button>@else<span></span>@endif
                    @if ($notifications->nextPageUrl())<x-button :href="$notifications->nextPageUrl()" variant="secondary" size="sm">Plus anciennes</x-button>@endif
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>
