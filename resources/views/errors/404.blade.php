<x-layouts.app title="Page introuvable" :noindex="true">
    <div class="mx-auto max-w-xl py-10 text-center sm:py-16">
        <x-cauri class="mx-auto mb-6 size-12 text-secondary-400" />
        <h1 class="text-balance text-[38px] sm:text-5xl">Cette page s'est perdue en route.</h1>
        <p class="mt-4 text-ink-soft">Le lien est peut-être ancien, ou la page a été déplacée.</p>
        <x-button :href="route('home')" size="lg" class="mt-8">Retour à l'accueil</x-button>
    </div>
</x-layouts.app>
