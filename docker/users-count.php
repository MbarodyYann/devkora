<?php

// Nombre de membres en base : 0 au premier lancement, la démo est alors semée (voir entrypoint.sh).
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo App\Models\User::count();
