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

    /*
    |--------------------------------------------------------------------------
    | Étiquette « Nouveau »
    |--------------------------------------------------------------------------
    |
    | Membre inscrit depuis moins de N jours (02, « Bienveillance »). 7 tant que
    | la plateforme est jeune (décision du 10 oct.), 14 ensuite.
    |
    */

    'new_member_days' => 7,

];
