<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aucun appel réseau sortant pendant les tests : le paiement est une simulation, rien ne doit partir.
        Http::preventStrayRequests();
    }
}
