<x-layouts.app title="Mot de passe oublié" :noindex="true">
    <x-auth-card title="Ça arrive à tout le monde." subtitle="Donne ton adresse e-mail : tu reçois un lien pour choisir un nouveau mot de passe.">
        <form method="post" action="{{ route('password.email') }}" class="space-y-5" novalidate>
            @csrf
            <x-field label="Adresse e-mail" name="email">
                <x-input name="email" type="email" autocomplete="email" required autofocus />
            </x-field>
            <x-button type="submit" size="lg" class="w-full">Recevoir le lien</x-button>
        </form>
        <x-slot:footer>
            <a href="{{ route('login') }}" class="text-sm text-ink-muted underline-offset-4 hover:text-ink hover:underline">Retour à la connexion</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
