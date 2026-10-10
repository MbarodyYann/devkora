<div class="mx-auto max-w-3xl">
    <a href="{{ route('listings.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-left" class="size-4" /> Toutes les annonces
    </a>

    @if (! $this->listingType)
        {{-- Étape 1 : le type d'annonce (11, « Annonces ») --}}
        <h1 class="text-[38px] sm:text-5xl">Tu cherches du renfort ? Dis-le à la communauté.</h1>
        <p class="mt-3 text-ink-soft">Choisis d'abord ce que tu cherches : le formulaire s'adapte.</p>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2">
            @foreach (App\Enums\ListingType::cases() as $choice)
                <li class="min-w-0">
                    <button type="button" wire:click="$set('type', '{{ $choice->value }}')"
                            class="flex h-full w-full flex-col items-start rounded-2xl border border-line bg-surface p-6 text-left shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover sm:rounded-card">
                        <x-icon :name="$choice === App\Enums\ListingType::Team ? 'users' : 'megaphone'" class="size-7 text-primary-600 dark:text-secondary-400" />
                        <span class="mt-4 font-serif text-2xl font-medium text-ink">{{ $choice->choice() }}</span>
                        <span class="mt-2 text-ink-soft">
                            {{ $choice === App\Enums\ListingType::Team
                                ? 'Un hackathon, un projet open source, une startup en création : trouve des devs d\'autres pays.'
                                : 'Tu es débordé : confie une partie de ton travail à un dev de confiance, payé en FCFA.' }}
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>
    @else
        @php($team = $this->listingType === App\Enums\ListingType::Team)
        <h1 class="text-[38px] sm:text-5xl">{{ $team ? 'Trouve tes coéquipiers.' : 'Confie ta mission à un dev de confiance.' }}</h1>
        <p class="mt-3 text-ink-soft">
            {{ $team ? 'Décris le projet et les rôles : les devs intéressés t\'écrivent, tu choisis qui rejoint l\'équipe.' : 'Décris le travail, ton budget et ton délai : les devs proposent leur prix, tu compares et tu retiens.' }}
            <button type="button" wire:click="$set('type', '')" class="font-semibold text-primary-600 underline-offset-4 hover:underline dark:text-primary-300">Changer de type</button>
        </p>

        <x-card class="mt-8">
            <form wire:submit="publish" class="space-y-6" novalidate>
                <x-field label="Titre" name="title" :hint="$team ? 'Ex. « Hackathon fintech à Abidjan : il nous manque un dev back-end »' : 'Ex. « Intégrer Orange Money dans une caisse Laravel »'">
                    <x-input name="title" wire:model="title" required minlength="15" maxlength="150" aria-describedby="title-hint" />
                </x-field>

                <x-field label="Description" name="description" hint="Le contexte, ce qui est attendu, ce qui existe déjà. Markdown accepté.">
                    <x-textarea name="description" wire:model="description" rows="8" required maxlength="10000" aria-describedby="description-hint" />
                </x-field>

                <x-field label="Compétences" name="skills" hint="1 à 6, séparées par des virgules. Ex. Laravel, Orange Money">
                    <x-input name="skills" wire:model.live.debounce.500ms="skills" list="competences" maxlength="300" aria-describedby="skills-hint" autocomplete="off" />
                    <datalist id="competences">
                        @foreach ($suggestedSkills as $skill)
                            <option value="{{ $skill }}"></option>
                        @endforeach
                    </datalist>
                </x-field>

                @if ($team)
                    <div class="grid gap-6 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                        <x-field label="Rôles recherchés" name="rolesWanted" hint="Ex. Dev back-end, designer">
                            <x-input name="rolesWanted" wire:model="rolesWanted" maxlength="150" aria-describedby="rolesWanted-hint" />
                        </x-field>
                        <x-field label="Heures par semaine" name="hoursPerWeek">
                            <x-input name="hoursPerWeek" wire:model="hoursPerWeek" inputmode="numeric" placeholder="10" />
                        </x-field>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-field label="Budget (FCFA)" name="budget" hint="Montant total. Ex. 150 000">
                            <x-input name="budget" wire:model.blur="budget" inputmode="numeric" placeholder="150000" aria-describedby="budget-hint" />
                        </x-field>
                        <x-field label="Délai souhaité (jours)" name="deliveryDays">
                            <x-input name="deliveryDays" wire:model="deliveryDays" inputmode="numeric" placeholder="10" />
                        </x-field>
                    </div>
                    {{-- Estimateur (02, P1) : seulement avec 3 missions comparables ou plus. --}}
                    @if ($estimate = $this->estimate)
                        <p class="flex items-start gap-2 rounded-field bg-primary-50 px-3.5 py-3 text-sm text-primary-700 dark:bg-primary-900 dark:text-primary-200" data-estimate>
                            <x-icon name="chart" class="mt-0.5 size-4 shrink-0" />
                            <span>Les missions similaires ({{ $estimate['label'] }}) ont un budget médian de <strong class="font-semibold">{{ App\Support\Money::fcfa($estimate['median']) }}</strong>.</span>
                        </p>
                    @endif
                @endif

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="min-w-0 space-y-3">
                        <x-field label="Pays" name="country">
                            <x-select name="country" wire:model="country" :options="$countries" placeholder="Choisir un pays" :disabled="$remote" />
                        </x-field>
                        <label class="inline-flex min-h-11 items-center gap-2 text-ink-soft">
                            <input type="checkbox" wire:model.live="remote" class="size-5 rounded border-line accent-primary-600"> Télétravail, depuis n'importe quel pays
                        </label>
                    </div>
                    <x-field label="Date limite" name="deadline" optional>
                        <x-input name="deadline" type="date" wire:model="deadline" :min="now()->addDay()->toDateString()" />
                    </x-field>
                </div>

                <div class="flex flex-col items-center gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
                    <p class="text-sm text-ink-muted sm:mr-auto">{{ $team ? 'Gratuit · Tu choisis qui rejoint l\'équipe.' : 'Gratuit · Aucun paiement avant d\'avoir retenu ton dev.' }}</p>
                    <x-button type="submit" size="lg" class="w-full sm:w-auto" wire:loading.attr="disabled">Publier mon annonce</x-button>
                </div>
            </form>
        </x-card>
    @endif
</div>
