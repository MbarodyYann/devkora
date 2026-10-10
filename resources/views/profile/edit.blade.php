@php($profile = $dev->profile)

<x-layouts.app title="Modifier mon profil" :noindex="true">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('devs.show', $dev) }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
            <x-icon name="arrow-left" class="size-4" /> Retour à mon profil
        </a>
        <h1 class="text-balance text-[38px] sm:text-5xl">Fais-toi trouver par les bons devs.</h1>
        <p class="mt-3 text-ink-soft">L'annuaire filtre par compétence, pays et langue : plus ton profil est précis, plus tu apparais dans les bonnes recherches.</p>

        <x-card class="mt-8">
            <form method="post" action="{{ route('profile.update', $dev) }}" enctype="multipart/form-data" class="space-y-6" novalidate>
                @csrf
                @method('put')

                {{-- Champ fichier natif masqué : son bouton serait dans la langue du navigateur (« Choose File »). --}}
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center" x-data="{ file: '' }">
                    <x-avatar :user="$dev" size="lg" />
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <p class="flex items-baseline justify-between gap-2 text-sm font-medium text-ink"><span>Photo</span><span class="font-normal text-ink-muted">Facultatif</span></p>
                        <div class="flex min-w-0 flex-wrap items-center gap-3">
                            <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp" class="peer sr-only"
                                   aria-describedby="avatar-hint{{ $errors->has('avatar') ? ' avatar-error' : '' }}"
                                   x-on:change="file = $event.target.files[0]?.name ?? ''">
                            <label for="avatar" class="btn-outline inline-flex min-h-11 cursor-pointer items-center rounded-btn px-4 text-[15px] font-semibold peer-focus-visible:outline-2 peer-focus-visible:outline-primary-600">Choisir une photo</label>
                            <span class="min-w-0 truncate text-sm text-ink-muted" x-text="file || 'Aucune photo choisie'">Aucune photo choisie</span>
                        </div>
                        <p id="avatar-hint" class="text-sm text-ink-muted">JPG, PNG ou WebP, 4 Mo au plus. Recadrée en carré.</p>
                        @error('avatar')
                            <p id="avatar-error" class="flex items-start gap-1.5 text-sm font-medium text-secondary-800 dark:text-secondary-300"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> {{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <x-field label="Nom complet" name="name">
                    <x-input name="name" :value="$dev->name" required maxlength="80" autocomplete="name" />
                </x-field>

                <x-field label="Titre" name="headline" hint="Ex. « Dev Laravel et Flutter »" optional>
                    <x-input name="headline" :value="$profile->headline" maxlength="80" aria-describedby="headline-hint" />
                </x-field>

                <x-field label="Présentation" name="bio" hint="Ce que tu construis, ce que tu sais faire, ce que tu cherches. 1 000 caractères au plus." optional>
                    <x-textarea name="bio" :value="$profile->bio" rows="5" maxlength="1000" aria-describedby="bio-hint" />
                </x-field>

                <div class="grid gap-6 sm:grid-cols-2">
                    <x-field label="Pays" name="country">
                        <x-select name="country" :options="$countries" :value="$profile->country" required />
                    </x-field>
                    <x-field label="Ville" name="city">
                        <x-input name="city" :value="$profile->city" required maxlength="80" autocomplete="address-level2" />
                    </x-field>
                </div>

                <x-field label="Compétences" name="skills" hint="Sépare-les par des virgules, 12 au plus. Ex. Laravel, Flutter, Wave" optional>
                    <x-input name="skills" :value="$dev->skills->pluck('name')->join(', ')" list="competences" maxlength="600" aria-describedby="skills-hint" />
                    <datalist id="competences">
                        @foreach ($suggestedSkills as $skill)
                            <option value="{{ $skill }}"></option>
                        @endforeach
                    </datalist>
                </x-field>

                <fieldset class="min-w-0">
                    <legend class="text-sm font-medium text-ink">Langues parlées</legend>
                    <p id="languages-hint" class="mt-1 text-sm text-ink-muted">6 au plus.</p>
                    @php($spoken = old('languages', $profile->languages ?? []))
                    <div class="mt-3 grid grid-cols-2 gap-x-4 sm:grid-cols-3">
                        @foreach ($languageOptions as $code => $label)
                            <label class="flex min-h-11 items-center gap-2 text-ink-soft">
                                <input type="checkbox" name="languages[]" value="{{ $code }}" @checked(in_array($code, $spoken, true)) class="size-5 rounded border-line accent-primary-600">
                                {{ Str::ucfirst($label) }}
                            </label>
                        @endforeach
                    </div>
                    @error('languages')
                        <p class="mt-1.5 flex items-start gap-1.5 text-sm font-medium text-secondary-800 dark:text-secondary-300"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> {{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="min-w-0">
                    <legend class="text-sm font-medium text-ink">Disponibilité</legend>
                    @php($current = old('availability', $profile->availability->value))
                    <div class="mt-3 space-y-1">
                        @foreach ($availabilities as $value => $label)
                            <label class="flex min-h-11 items-center gap-2 text-ink-soft">
                                <input type="radio" name="availability" value="{{ $value }}" @checked($current === $value) class="size-5 border-line accent-primary-600">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('availability')
                        <p class="mt-1.5 flex items-start gap-1.5 text-sm font-medium text-secondary-800 dark:text-secondary-300"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> {{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="grid gap-6 sm:grid-cols-2">
                    <x-field label="Lien GitHub" name="github_url" optional>
                        <x-input name="github_url" type="url" :value="$profile->github_url" placeholder="https://github.com/…" maxlength="255" />
                    </x-field>
                    <x-field label="Portfolio" name="portfolio_url" optional>
                        <x-input name="portfolio_url" type="url" :value="$profile->portfolio_url" placeholder="https://…" maxlength="255" />
                    </x-field>
                </div>

                <div class="flex flex-col items-center gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
                    <p class="text-sm text-ink-muted sm:mr-auto">Visible tout de suite dans l'annuaire.</p>
                    <x-button type="submit" size="lg" class="w-full sm:w-auto">Enregistrer mon profil</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
