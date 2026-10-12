<?php

use App\Http\Controllers\HomeController;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Listing;
use App\Models\Question;
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
        ->and(substr_count($html, '>'.Copy::SIGNUP_CTA.'</a>'))->toBe(3); // navigation, hero et CTA final
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
        ->assertSeeInOrder(['Gratuit, sans abonnement', 'Pensé pour ton contexte', 'Une réputation qui te suit'])
        ->assertSee('missions payées en Mobile Money')
        ->assertDontSee('carte bancaire')
        ->assertSee('data-stat="devs">4<', false)
        ->assertSee('data-stat="countries">2<', false)
        ->assertSee('🇸🇳')
        ->assertSee('🇬🇭')
        ->assertDontSee('Pensé pour</dt>', false);

    // J1 : « Devs inscrits » affiche le chiffre seul, sans pile d'avatars.
    $html = $this->get('/')->getContent();
    $devs = substr($html, strpos($html, 'Devs inscrits'), strpos($html, '>Pays<') - strpos($html, 'Devs inscrits'));
    expect(substr_count($devs, '<dd'))->toBe(1);
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
        ->assertSee('Poser une question')
        ->assertDontSee(Copy::SIGNUP_CTA);
});

test('bande de confiance : sur une ligne, 5 drapeaux sous 360 px, 6 sous 640 px et 8 au-dessus, puis le reste en texte', function () {
    foreach (['SN', 'CI', 'TG', 'BJ', 'ML', 'BF', 'CM', 'GN', 'NE', 'GH'] as $country) {
        User::factory()->withProfile(['country' => $country])->create();
    }

    $html = $this->get('/')->getContent();
    $flags = substr($html, strpos($html, 'data-flags'), 1500);

    expect(substr_count($flags, '<span class="">') + substr_count($flags, '<span>'))->toBe(5)
        ->and(substr_count($flags, '<span class="max-[360px]:hidden">'))->toBe(1)
        ->and(substr_count($flags, '<span class="max-sm:hidden">'))->toBe(2)
        ->and($flags)->toContain('max-[360px]:inline">+5</span>')
        ->toContain('max-[360px]:hidden sm:hidden">+4</span>')
        ->toContain('sm:inline">+2</span>')
        ->and($html)->toContain('flex-nowrap items-center gap-1 text-base leading-7" aria-hidden="true" data-flags');
});

test('le sous-titre du hero et tous les H1 et H2 sont équilibrés (pas de mot orphelin)', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toMatch('/h1,\s+h2 \{\s+text-wrap: balance;/')
        ->and($this->get('/')->getContent())->toContain('<p class="mt-6 max-w-xl text-balance');
});

test('questions résolues : colonne de la bande de confiance', function () {
    $resolved = Question::factory()->create(['title' => 'Question résolue sur le webhook Wave']);
    $answer = Answer::factory()->for($resolved)->create();
    $resolved->forceFill(['accepted_answer_id' => $answer->id])->save();
    Question::factory()->create(['title' => 'Question encore ouverte sur USSD']);

    $this->get('/')
        ->assertSee('Questions résolues')
        ->assertSee('data-stat="resolved">1<', false);
});

test('questions du moment : classement façon Hacker News, en SQL', function () {
    $this->freezeTime();
    $old = Question::factory()->create(['title' => 'Vieille question très votée', 'score' => 8, 'created_at' => now()->subDays(10)]);
    $fresh = Question::factory()->create(['title' => 'Question récente avec réponses', 'answers_count' => 3, 'created_at' => now()->subHours(2)]);
    $unanswered = Question::factory()->create(['title' => 'Question sans réponse de ce matin', 'created_at' => now()->subHours(5)]);
    Question::factory()->create(['title' => 'Question masquée', 'score' => 50, 'answers_count' => 9, 'created_at' => now()->subHour(), 'hidden_at' => now()]);
    Question::factory()->create(['title' => 'Question d\'il y a 20 jours', 'created_at' => now()->subDays(20)]);

    // 2 h et 3 réponses : (0 + 6 + 1) / 4^1,5 ≈ 0,88 ; 10 jours et 8 votes : 9 / 242^1,5 ≈ 0,002.
    expect(Question::query()->trending()->pluck('id')->all())->toBe([$fresh->id, $unanswered->id, $old->id])
        // Moins de 5 questions sur 14 jours : l'accueil élargit à 30 jours.
        ->and(Question::query()->trending(30)->count())->toBe(4);

    $html = $this->get('/')->getContent();
    $section = trendingSection($html);

    expect($section)->toContain('Ce que les devs se demandent en ce moment.')
        ->toContain('Réponds si tu sais, ou pose ta propre question.')
        ->toContain('Voir toutes les questions →')
        ->not->toContain('Question masquée')
        ->and(strpos($section, 'Question récente avec réponses'))->toBeLessThan(strpos($section, 'Question sans réponse de ce matin'))
        ->and(strpos($section, 'Question sans réponse de ce matin'))->toBeLessThan(strpos($section, 'Vieille question très votée'))
        ->and(strpos($section, 'Vieille question très votée'))->toBeLessThan(strpos($section, 'Question d&#039;il y a 20 jours'))
        ->and($html)->not->toContain('Ces blocages ont déjà trouvé leur solution.');
});

test('questions du moment : 14 jours suffisent dès qu\'il y a 5 questions ; cache de 10 minutes', function () {
    Question::factory()->count(5)->create(['created_at' => now()->subDays(3)]);
    Question::factory()->create(['title' => 'Question d\'il y a 20 jours', 'score' => 30, 'created_at' => now()->subDays(20)]);

    $this->get('/')->assertDontSee('Question d&#039;il y a 20 jours', false);

    Question::factory()->create(['title' => 'Question toute neuve', 'answers_count' => 5]);
    $this->get('/')->assertDontSee('Question toute neuve');

    Cache::forget('home.trending');
    $this->get('/')->assertSee('Question toute neuve');
});

test('questions du moment : carte vivante (depuis quand, réponses, score, Résolue, auteur et pays)', function () {
    $this->freezeTime();
    $author = User::factory()->withProfile(['country' => 'CI'])->create(['name' => 'Awa Koné']);
    $question = Question::factory()->for($author)->create(['title' => 'Question résolue il y a 3 h', 'score' => 4, 'answers_count' => 2, 'created_at' => now()->subHours(3)]);
    $question->forceFill(['accepted_answer_id' => Answer::factory()->for($question)->create()->id])->save();

    $html = $this->get('/')->getContent();
    $card = trendingSection($html);

    expect($card)->toContain('il y a 3 h')
        ->toContain('Awa Koné')
        ->toContain('🇨🇮')
        ->toContain('<span class="sr-only">Côte d&#039;Ivoire</span>')
        ->toContain('Résolue')
        ->toContain('>4</dd>')
        ->toContain('>2</dd>');
});

test('questions du moment : un seul bouton « Pose ta question », les visiteurs passent par la connexion', function () {
    Question::factory()->create();

    $html = $this->get('/')->getContent();
    expect(substr_count($html, '>Pose ta question</a>'))->toBe(1)
        ->and($html)->toContain('href="'.route('questions.create').'"');

    $this->get(route('questions.create'))->assertRedirect(route('login'));
    expect(session('url.intended'))->toBe(route('questions.create'));
});

test('le membre voit « Poser une question » sur l\'accueil', function () {
    $this->actingAs(User::factory()->withProfile()->create())->get('/')->assertSee('Poser une question');
});

test('bande de confiance finale : devs, pays, questions résolues, articles partagés ; derniers articles', function () {
    Article::factory()->count(2)->create();
    $latest = Article::factory()->create(['title' => 'Le tout dernier article publié', 'created_at' => now()->addMinute()]);

    $html = $this->get('/')->getContent();
    $band = substr($html, strpos($html, 'aria-label="DevKora en chiffres"'), 5000);

    preg_match_all('/<dt[^>]*>([^<]+)<\/dt>/', $band, $labels);
    expect(array_map('trim', $labels[1]))->toBe(['Devs inscrits', 'Pays', 'Questions résolues', 'Articles partagés'])
        ->and($band)->toContain('data-stat="articles">3<')
        ->and($html)->toContain('Ce que les devs du continent ont appris, pour toi.')
        ->and(strpos($html, 'Le tout dernier article publié'))->toBeGreaterThan(strpos($html, 'articles-titre'));
});

test('accueil : section des annonces ouvertes, équipes et missions mélangées', function () {
    Listing::factory()->create(['title' => 'Mission ouverte de démonstration']);
    Listing::factory()->team()->create(['title' => 'Équipe ouverte de démonstration']);
    Listing::factory()->create(['title' => 'Mission annulée de démonstration', 'status' => 'cancelled']);

    $this->get('/')
        ->assertSee('Des devs cherchent du renfort, peut-être le tien.')
        ->assertSee('Mission ouverte de démonstration')
        ->assertSee('Équipe ouverte de démonstration')
        ->assertDontSee('Mission annulée de démonstration');
});

/** La section « Questions du moment » de l'accueil, de son ouverture à sa fermeture. */
function trendingSection(string $html): string
{
    $start = strpos($html, 'data-trending');

    return substr($html, $start, strpos($html, '</section>', $start) - $start);
}
