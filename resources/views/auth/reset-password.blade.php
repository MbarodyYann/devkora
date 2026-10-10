<x-layouts.app title="Nouveau mot de passe" :noindex="true">
    <x-auth-card title="Choisis ton nouveau mot de passe." subtitle="Ensuite, connecte-toi avec ce nouveau mot de passe.">
        <form method="post" action="{{ route('password.update') }}" class="space-y-5" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-field label="Adresse e-mail" name="email">
                <x-input name="email" type="email" autocomplete="email" required :value="$request->email" />
            </x-field>
            <x-field label="Nouveau mot de passe" name="password" hint="8 caractères au moins.">
                <x-input name="password" type="password" autocomplete="new-password" required autofocus aria-describedby="password-hint" />
            </x-field>
            <x-field label="Confirme le mot de passe" name="password_confirmation">
                <x-input name="password_confirmation" type="password" autocomplete="new-password" required />
            </x-field>
            <x-button type="submit" size="lg" class="w-full">Enregistrer mon mot de passe</x-button>
        </form>
    </x-auth-card>
</x-layouts.app>
