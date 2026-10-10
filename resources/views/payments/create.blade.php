@php($dev = $proposal->user)

<x-layouts.app title="Sécuriser les fonds" :noindex="true">
    <div class="mx-auto max-w-xl">
        <a href="{{ route('listings.show', $listing).'#propositions' }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
            <x-icon name="arrow-left" class="size-4" /> Retour aux propositions
        </a>
        <h1 class="text-[34px] sm:text-[44px]">Retiens {{ $dev->firstName() }} et sécurise les fonds.</h1>
        <p class="mt-3 text-ink-soft">L'argent est bloqué jusqu'à la fin de la mission : {{ $dev->firstName() }} sait qu'il sera payé, et toi, tu ne paies qu'un travail livré.</p>

        <x-card class="mt-6">
            <dl class="space-y-2">
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">Mission</dt><dd class="min-w-0 truncate text-right font-medium text-ink">{{ $listing->title }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">Dev</dt><dd class="font-medium text-ink">{{ $dev->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">Délai</dt><dd class="text-ink">{{ $proposal->delivery_days }} jours</dd></div>
                <div class="flex items-baseline justify-between gap-3 border-t border-line pt-3"><dt class="text-ink-muted">À sécuriser</dt><dd><x-budget :amount="$proposal->proposed_fcfa" /></dd></div>
            </dl>

            <form method="post" action="{{ route('payments.store', [$listing, $proposal]) }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <fieldset class="min-w-0">
                    <legend class="text-sm font-medium text-ink">Opérateur Mobile Money</legend>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($providers as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="provider" value="{{ $value }}" @checked(old('provider') === $value) class="peer sr-only">
                                <span class="flex min-h-11 items-center justify-center rounded-btn border border-line px-2 text-center text-sm font-semibold text-ink transition peer-checked:border-secondary-400 peer-checked:bg-secondary-400 peer-checked:text-primary-950 peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('provider')
                        <p class="mt-1.5 flex items-start gap-1.5 text-sm font-medium text-secondary-800 dark:text-secondary-300"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> {{ $message }}</p>
                    @enderror
                </fieldset>
                <x-field label="Ton numéro Mobile Money" name="phone" hint="Format international, par exemple +225 07 07 07 07 07.">
                    <x-input name="phone" type="tel" inputmode="tel" autocomplete="tel" required aria-describedby="phone-hint" />
                </x-field>
                <x-simulation-notice />
                <x-button type="submit" size="lg" class="w-full">Sécuriser {{ App\Support\Money::fcfa($proposal->proposed_fcfa) }}</x-button>
                <p class="text-center text-sm text-ink-muted">Étape suivante : un code de confirmation (simulé).</p>
            </form>
        </x-card>
    </div>
</x-layouts.app>
