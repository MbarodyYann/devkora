<x-layouts.app title="Confirme ton mot de passe" :noindex="true">
    <x-auth-card title="Confirme ton mot de passe." subtitle="Une vérification rapide avant de continuer.">
        <form method="post" action="{{ route('password.confirm.store') }}" class="space-y-5" novalidate>
            @csrf
            <x-field label="Mot de passe" name="password">
                <x-input name="password" type="password" autocomplete="current-password" required autofocus />
            </x-field>
            <x-button type="submit" size="lg" class="w-full">Confirmer</x-button>
        </form>
    </x-auth-card>
</x-layouts.app>
