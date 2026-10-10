<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Tests Feature : PostgreSQL (base devkora_test, phpunit.xml), remise à zéro à chaque test.
| Tests Browser : mesures réelles dans Chromium (Playwright) sur la base semée.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit', 'Reset', 'Browser');
