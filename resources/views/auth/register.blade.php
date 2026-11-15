<x-layouts.app title="Inscription" description="Crée ton profil gratuit sur DevKora : nom, e-mail, pays, ville et mot de passe. Ton profil est en ligne en 2 minutes.">
    <x-auth-card title="Ton profil en ligne en 2 minutes." subtitle="Nom, e-mail, pays, ville et mot de passe. Tes compétences viendront ensuite.">
        <form method="post" action="{{ route('register.store') }}" class="space-y-5" novalidate>
            @csrf
            <x-field label="Nom complet" name="name">
                <x-input name="name" autocomplete="name" required autofocus maxlength="80" placeholder="Ex. Aïssatou Koné" />
            </x-field>
            <x-field label="Adresse e-mail" name="email">
                <x-input name="email" type="email" autocomplete="email" required maxlength="255" />
            </x-field>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="Pays" name="country">
                    <x-select name="country" :options="$countries" value="SN" required autocomplete="country" />
                </x-field>
                <x-field label="Ville" name="city">
                    <x-input name="city" autocomplete="address-level2" required maxlength="80" placeholder="Ex. Dakar" />
                </x-field>
            </div>
            <x-field label="Mot de passe" name="password" hint="8 caractères au moins.">
                <x-input name="password" type="password" autocomplete="new-password" required aria-describedby="password-hint" />
            </x-field>
            <x-field label="Confirme le mot de passe" name="password_confirmation">
                <x-input name="password_confirmation" type="password" autocomplete="new-password" required />
            </x-field>
            <div class="space-y-3 pt-1">
                <x-button type="submit" size="lg" class="w-full">{{ App\Support\Copy::SIGNUP_CTA }}</x-button>
                <p class="text-center text-sm text-ink-muted">{{ App\Support\Copy::SIGNUP_REASSURANCE }}</p>
            </div>
        </form>
    </x-auth-card>
</x-layouts.app>
