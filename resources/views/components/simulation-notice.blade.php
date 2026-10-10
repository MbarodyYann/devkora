{{-- Bandeau obligatoire autour du paiement (11) : aucune promesse fausse, le paiement est simulé. --}}
<p {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-field border border-secondary-400 bg-secondary-50 px-3.5 py-2.5 text-sm font-medium text-secondary-800 dark:bg-secondary-950 dark:text-secondary-300']) }} data-simulation>
    <x-icon name="alert" class="mt-0.5 size-4 shrink-0" /> Simulation : aucun argent réel n'est débité.
</p>
