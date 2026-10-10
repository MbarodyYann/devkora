// Alpine seul, pour les pages SANS composant Livewire (accueil, profils, connexion) : environ 16 Ko
// compressés au lieu de livewire.min.js. Même version que celle embarquée par Livewire (3.15.12).
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
