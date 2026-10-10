<?php

use App\Http\Controllers\HomeController;
use App\Models\User;
use App\Support\Copy;
use Illuminate\Support\Facades\Cache;

test('le hero reprend l\'identité : titre, promesse, CTA unique et réassurance', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>DevKora · Le point de rencontre des devs africains</title>')
        ->toContain('Le point de rencontre')
        ->toContain('<em class="block font-serif italic text-secondary-400">des devs africains.</em>')
        ->toContain('Trouve des missions. Partage tes connaissances. Construis avec les tiens.')
        ->toContain(Copy::SIGNUP_REASSURANCE)
        ->and(substr_count($html, 'data-cta="principal"'))->toBe(1)
        ->and(substr_count($html, '>'.Copy::SIGNUP_CTA.'</a>'))->toBe(2); // hero et navigation
});

test('l\'image du hero : WebP en deux tailles, dimensions et texte alternatif, chargée à la demande sur mobile', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('srcset="/images/hero/portrait-480.webp 480w, /images/hero/portrait-800.webp 800w"')
        ->toContain('srcset="/images/hero/paysage-480.webp 480w, /images/hero/paysage-800.webp 800w"')
        ->toContain('width="800" height="450"')
        ->toContain('alt="Deux développeurs travaillent ensemble sur du code"')
        ->toContain('loading="lazy"')
        ->toContain('media="(min-width: 1024px)" fetchpriority="high"');

    foreach (['portrait-480' => 40_000, 'portrait-800' => 80_000, 'paysage-480' => 40_000, 'paysage-800' => 80_000] as $file => $max) {
        expect(filesize(public_path("images/hero/$file.webp")))->toBeLessThanOrEqual($max);
    }
});

test('bande de garanties et bande de confiance aux chiffres réels', function () {
    User::factory()->count(3)->withProfile(['country' => 'SN'])->create();
    User::factory()->withProfile(['country' => 'GH'])->create();
    User::factory()->create(); // sans profil : pas compté

    $this->get('/')
        ->assertSeeInOrder(['Gratuit, sans carte bancaire', 'Pensé pour ton contexte', 'Une réputation qui te suit'])
        ->assertSee('data-stat="devs">4<', false)
        ->assertSee('data-stat="countries">2<', false)
        ->assertSee('🇸🇳')
        ->assertSee('🇬🇭')
        ->assertSee('Wave · Orange Money · MTN MoMo');
});

test('les chiffres sont mis en cache 10 minutes', function () {
    User::factory()->withProfile()->create();
    $this->get('/');
    User::factory()->withProfile()->create();

    $this->get('/')->assertSee('data-stat="devs">1<', false);

    Cache::forget('home.stats');
    $this->get('/')->assertSee('data-stat="devs">2<', false);
    expect(HomeController::STATS_TTL)->toBe(600);
});

test('un membre voit son action, pas le CTA d\'inscription', function () {
    $this->actingAs(User::factory()->withProfile()->create())
        ->get('/')
        ->assertSee('Trouver des devs')
        ->assertDontSee(Copy::SIGNUP_CTA);
});
