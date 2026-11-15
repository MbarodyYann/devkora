<?php

// La base configurée (DB_*) répond-elle ? Sortie 0 si oui. Délai court : le site ne doit pas attendre.
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;connect_timeout=5', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '5432', getenv('DB_DATABASE') ?: 'devkora');
try {
    new PDO($dsn, getenv('DB_USERNAME') ?: '', getenv('DB_PASSWORD') ?: '');
    exit(0);
} catch (Throwable) {
    exit(1);
}
