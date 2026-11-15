{{-- Cauri, l'unité de réputation : coquille ovale, fente centrale dentée (08, « Cauri »).
     Contour et fente en currentColor (secondary-400), cernés de secondary-600 en clair pour rester visibles
     sur fond blanc ; coquille blanche. Une classe passée remplace la taille et la couleur par défaut. --}}
<svg class="{{ $attributes->get('class', 'size-4 text-secondary-400') }}" {{ $attributes->except('class') }} viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
    <path fill="currentColor" class="stroke-secondary-600 dark:stroke-transparent" stroke-width="1" d="M12 .6C17.6.6 20.6 5.6 20.6 12c0 6.8-3.6 11.4-8.6 11.4S3.4 18.8 3.4 12C3.4 5.6 6.4.6 12 .6Z"/>
    <path class="fill-white" d="M11.4 2.4c4 0 6.6 4 6.6 9.6 0 5.8-2.9 9.7-6.6 9.7-3.6 0-6.3-3.9-6.3-9.7 0-5.6 2.6-9.6 6.3-9.6Z"/>
    <path fill="currentColor" d="M13.05 3 13.49 4.29 13.76 5.57 13.94 6.86 14.01 8.14 13.97 9.43 13.81 10.71 13.55 12 13.18 13.29 12.73 14.57 12.22 15.86 11.67 17.14 11.11 18.43 10.54 19.71 9.89 21 L9.59 21A1 1 0 0 1 9.57 19A1.01 1.01 0 0 1 9.89 17A1.03 1.03 0 0 1 10.37 15A1.03 1.03 0 0 1 10.89 13A1.03 1.03 0 0 1 11.39 11A1.02 1.02 0 0 1 11.8 9A1.01 1.01 0 0 1 12.11 7A1.01 1.01 0 0 1 12.36 5A1.02 1.02 0 0 1 12.75 3Z"/>
</svg>
