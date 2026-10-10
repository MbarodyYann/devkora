<?php

/*
| Routeur du serveur PHP intégré pour les tests navigateur : fichiers statiques servis tels quels,
| le reste par Laravel. Contrairement au routeur de Laravel, il n'écrit rien sur la sortie standard.
*/

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if ($path !== '/' && is_file(__DIR__.'/../../public'.$path)) {
    return false;
}

require __DIR__.'/../../public/index.php';
