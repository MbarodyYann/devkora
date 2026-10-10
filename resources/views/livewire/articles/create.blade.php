<div class="mx-auto max-w-3xl">
    <a href="{{ route('articles.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-left" class="size-4" /> Tous les articles
    </a>
    <h1 class="text-[38px] sm:text-5xl">Ce que tu sais peut faire gagner des semaines à un autre dev.</h1>
    <p class="mt-3 text-ink-soft">Un tutoriel, un retour d'expérience ou tes bonnes pratiques : écris en Markdown, vérifie l'aperçu, puis publie.</p>

    <x-card class="mt-8">
        <form wire:submit="publish" class="space-y-6" novalidate>
            <div class="grid gap-6 sm:grid-cols-2">
                <x-field label="Type d'article" name="category">
                    <x-select name="category" wire:model="category" :options="$categories" placeholder="Choisir" required />
                </x-field>
                <x-field label="Tags" name="tags" hint="1 à 5, séparés par des virgules.">
                    <x-input name="tags" wire:model="tags" list="tags-proposes" maxlength="300" aria-describedby="tags-hint" autocomplete="off" placeholder="Ex. Laravel, Wave" />
                    <datalist id="tags-proposes">
                        @foreach ($suggestedTags as $tag)
                            <option value="{{ $tag }}"></option>
                        @endforeach
                    </datalist>
                </x-field>
            </div>

            <x-field label="Titre" name="title" hint="Dis ce que le lecteur va apprendre. Ex. « Intégrer l'API Wave de bout en bout »">
                <x-input name="title" wire:model="title" required minlength="15" maxlength="150" aria-describedby="title-hint" />
            </x-field>

            <div class="min-w-0 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label for="body" class="text-sm font-medium text-ink">Article</label>
                    <x-segmented label="Affichage de l'article" model="tab" :options="['ecrire' => 'Écrire', 'apercu' => 'Aperçu']" />
                </div>
                @if ($tab === 'apercu')
                    <div class="min-h-64 rounded-field border border-line bg-bg-alt p-4 sm:p-6" data-preview>
                        @if ($this->preview)
                            <div class="prose-dk prose-article">{!! $this->preview !!}</div>
                        @else
                            <p class="text-ink-muted">Rien à prévisualiser pour l'instant : écris ton article dans l'onglet « Écrire ».</p>
                        @endif
                    </div>
                @else
                    <x-textarea name="body" wire:model="body" rows="18" required maxlength="60000" class="font-mono" aria-describedby="body-hint" />
                @endif
                <p id="body-hint" class="text-sm text-ink-muted">Markdown : ## pour un intertitre, `code`, ```php pour un bloc de code, **gras**, - pour une liste.</p>
                @error('body')
                    <p id="body-error" class="flex items-start gap-1.5 text-sm font-medium text-secondary-800 dark:text-secondary-300"><x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> {{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col items-center gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
                <p class="text-sm text-ink-muted sm:mr-auto">Chaque vote « Utile » te rapporte +5 cauris.</p>
                <x-button type="submit" size="lg" class="w-full sm:w-auto" wire:loading.attr="disabled">Publier mon article</x-button>
            </div>
        </form>
    </x-card>
</div>
