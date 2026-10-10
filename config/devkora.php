<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode démo
    |--------------------------------------------------------------------------
    |
    | Connexion en un clic avec les comptes de démonstration (Moussa, Aïssatou,
    | Koffi). Actif seulement si APP_DEMO=true : jamais en production réelle.
    |
    */

    'demo' => (bool) env('APP_DEMO', false),

];
