<x-layouts.app title="Poser une question" :noindex="true">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('questions.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
            <x-icon name="arrow-left" class="size-4" /> Toutes les questions
        </a>
        <h1 class="text-[38px] sm:text-5xl">Explique ton blocage, la communauté répond.</h1>
        <p class="mt-3 text-ink-soft">Un titre clair, le code qui coince et les bons tags : les devs qui connaissent le sujet la trouvent par son tag.</p>

        <x-card class="mt-8">
            <form method="post" action="{{ route('questions.store') }}" class="space-y-6" novalidate>
                @csrf
                <x-field label="Titre" name="title" hint="Résume ton problème en une phrase, comme si tu le cherchais sur Google.">
                    <livewire:questions.similar-title :value="old('title')" />
                </x-field>

                <x-field label="Détail" name="body" hint="Ce que tu veux faire, ce que tu as essayé, le message d'erreur. Markdown accepté : `code`, ```php pour un bloc de code, **gras**.">
                    <x-textarea name="body" rows="12" required minlength="30" maxlength="20000" aria-describedby="body-hint" class="font-mono" />
                </x-field>

                <x-field label="Tags" name="tags" hint="1 à 5, séparés par des virgules. Ex. Laravel, Wave">
                    <x-input name="tags" required maxlength="300" list="tags-proposes" aria-describedby="tags-hint" autocomplete="off" />
                    <datalist id="tags-proposes">
                        @foreach ($suggestedTags as $tag)
                            <option value="{{ $tag }}"></option>
                        @endforeach
                    </datalist>
                </x-field>

                <div class="flex flex-col items-center gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
                    <p class="text-sm text-ink-muted sm:mr-auto">Gratuit · Ta question reste trouvable par tous, même dans un an.</p>
                    <x-button type="submit" size="lg" class="w-full sm:w-auto">Publier ma question</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
