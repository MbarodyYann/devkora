<x-layouts.app title="Connexion" description="Connecte-toi à DevKora pour retrouver ton profil et la communauté.">
    <x-auth-card title="Content de te revoir." :demo="true">
        <form method="post" action="{{ route('login.store') }}" class="space-y-5" novalidate>
            @csrf
            <x-field label="Adresse e-mail" name="email">
                <x-input name="email" type="email" autocomplete="email" required autofocus />
            </x-field>
            <x-field label="Mot de passe" name="password">
                <x-input name="password" type="password" autocomplete="current-password" required />
            </x-field>
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                <label class="inline-flex min-h-11 items-center gap-2 text-ink-soft">
                    <input type="checkbox" name="remember" value="1" class="size-5 rounded border-line accent-primary-600">
                    Rester connecté
                </label>
                <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">Mot de passe oublié ?</a>
            </div>
            <x-button type="submit" size="lg" class="w-full">Me connecter</x-button>
        </form>
    </x-auth-card>
</x-layouts.app>
