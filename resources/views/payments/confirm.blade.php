<x-layouts.app title="Confirmer le paiement" :noindex="true">
    <div class="mx-auto max-w-xl">
        <h1 class="text-[34px] sm:text-[44px]">Dernière étape : le code de confirmation.</h1>
        <p class="mt-3 text-ink-soft">En vrai, {{ $provider->label() }} t'enverrait ce code par SMS au {{ $maskedPhone }}. Ici, tout est simulé : le voici.</p>

        <x-card class="mt-6">
            <p class="text-sm text-ink-muted">Code reçu (simulation)</p>
            <p class="mt-1 font-mono text-3xl tracking-[.3em] text-ink" data-code>{{ $pending['code'] }}</p>

            <form method="post" action="{{ route('payments.verify', $listing) }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <x-field label="Saisis le code" name="code">
                    <x-input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus />
                </x-field>
                <x-simulation-notice />
                <x-button type="submit" size="lg" class="w-full">Confirmer et retenir {{ $proposal->user->firstName() }}</x-button>
                <p class="text-center text-sm text-ink-muted">Les fonds restent bloqués jusqu'à la fin de la mission, et te sont remboursés si tu l'annules.</p>
            </form>
        </x-card>
    </div>
</x-layouts.app>
