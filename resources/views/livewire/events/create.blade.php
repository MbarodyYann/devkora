<div class="mx-auto max-w-3xl">
    <a href="{{ route('events.index') }}" class="mb-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-ink-muted hover:text-ink">
        <x-icon name="arrow-left" class="size-4" /> Tous les événements
    </a>
    <h1 class="text-[38px] sm:text-5xl">Réunis les devs autour de toi.</h1>
    <p class="mt-3 text-ink-soft">Un meetup, un atelier ou un hackathon : décris le programme, la date et le lieu. Les devs intéressés indiquent qu'ils participent.</p>

    <x-card class="mt-8">
        <form wire:submit="publish" class="space-y-6" novalidate>
            <x-field label="Type" name="type">
                <x-select name="type" wire:model="type" :options="$types" placeholder="Choisir" />
            </x-field>
            <x-field label="Titre" name="title" hint="Ex. « Meetup Laravel Dakar : déployer sur un VPS local »">
                <x-input name="title" wire:model="title" maxlength="150" aria-describedby="title-hint" />
            </x-field>
            <x-field label="Description" name="description" hint="Le programme, pour qui, ce qu'il faut apporter. Markdown accepté.">
                <x-textarea name="description" wire:model="description" rows="6" maxlength="6000" aria-describedby="description-hint" />
            </x-field>
            <div class="grid gap-6 sm:grid-cols-2">
                <x-field label="Date" name="date"><x-input name="date" type="date" wire:model="date" :min="now()->toDateString()" /></x-field>
                <x-field label="Heure de début" name="startTime"><x-input name="startTime" type="time" wire:model="startTime" /></x-field>
                <x-field label="Date de fin" name="endDate" optional><x-input name="endDate" type="date" wire:model="endDate" :min="now()->toDateString()" /></x-field>
                <x-field label="Heure de fin" name="endTime"><x-input name="endTime" type="time" wire:model="endTime" /></x-field>
            </div>
            <label class="inline-flex min-h-11 items-center gap-2 text-ink-soft">
                <input type="checkbox" wire:model.live="online" class="size-5 rounded border-line accent-primary-600"> En ligne
            </label>
            @unless ($online)
                <div class="grid gap-6 sm:grid-cols-2">
                    <x-field label="Ville" name="city"><x-input name="city" wire:model="city" maxlength="80" /></x-field>
                    <x-field label="Pays" name="country"><x-select name="country" wire:model="country" :options="$countries" placeholder="Choisir un pays" /></x-field>
                </div>
            @endunless
            <x-field label="Lien d'inscription" name="registrationUrl" hint="Si l'organisateur gère les inscriptions ailleurs." optional>
                <x-input name="registrationUrl" type="url" wire:model="registrationUrl" placeholder="https://…" maxlength="255" aria-describedby="registrationUrl-hint" />
            </x-field>
            <div class="flex flex-col items-center gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
                <p class="text-sm text-ink-muted sm:mr-auto">Gratuit · Visible tout de suite dans les événements.</p>
                <x-button type="submit" size="lg" class="w-full sm:w-auto" wire:loading.attr="disabled">Publier mon événement</x-button>
            </div>
        </form>
    </x-card>
</div>
