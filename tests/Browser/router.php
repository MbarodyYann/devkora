<?php

/*
| Routeur du serveur PHP intégré pour les tests navigateur : fichiers statiques servis tels quels,
| le reste par Laravel. Contrairement au routeur de Laravel, il n'écrit rien sur la sortie standard.
|
| Il applique les en-têtes de sécurité de production, lus dans la configuration Nginx
| (deploy/nginx/devkora-security-headers.conf) : la CSP est donc éprouvée sur chaque page testée.
*/

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$conf = (string) file_get_contents(__DIR__.'/../../deploy/nginx/devkora-security-headers.conf');
preg_match_all('/^add_header\s+(\S+)\s+"([^"]*)"\s+always;$/m', $conf, $headers, PREG_SET_ORDER);
foreach ($headers as [, $name, $value]) {
    header("$name: $value");
}

if ($path !== '/' && is_file(__DIR__.'/../../public'.$path)) {
    return false;
}

require __DIR__.'/../../public/index.php';
