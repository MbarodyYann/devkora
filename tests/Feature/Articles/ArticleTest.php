<?php

use App\Enums\ArticleCategory;
use App\Livewire\Articles\Create;
use App\Livewire\Articles\Index;
use App\Models\Article;
use App\Models\Skill;
use App\Models\User;
use App\Services\ArticleService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function writer(): User
{
    return User::factory()->withProfile()->create();
}

function longBody(): string
{
    return "## Contexte\n\n".str_repeat('Une phrase utile pour expliquer le sujet en détail. ', 10)."\n\n```php\necho 'ok';\n```";
}

test('la liste reprend l\'en-tête de 11 ; « Écrire un article » pour les membres seulement', function () {
    $this->get('/articles')
        ->assertOk()
        ->assertSee('Ce que tu as appris')
        ->assertSee('<em class="font-serif italic text-secondary-400">peut débloquer un autre dev.</em>', false)
        ->assertSee('Tutoriels, retours d&#039;expérience et bonnes pratiques écrits par des devs du continent, pour ton contexte.', false)
        ->assertDontSee('Écrire un article');

    $this->actingAs(writer())->get('/articles')->assertSee('Écrire un article');
});

test('la navigation suit l\'ordre Questions · Articles · Devs', function () {
    $html = $this->get('/articles')->getContent();
    $nav = substr($html, strpos($html, 'aria-label="Navigation principale"'), 2000);

    preg_match_all('/>\s*(Questions|Articles|Devs)\s*</', $nav, $labels);

    expect($labels[1])->toBe(['Questions', 'Articles', 'Devs']);
});

test('le menu « Publier » des membres propose de poser une question et d\'écrire un article', function () {
    $this->get('/devs')->assertDontSee('data-publish-menu', false);

    $html = $this->actingAs(writer())->get('/devs')->getContent();
    $menu = substr($html, strpos($html, 'data-publish-menu'), 3000);

    expect($menu)->toContain('Publier')->toContain('Poser une question')->toContain('Écrire un article')
        ->and(substr_count($html, 'Écrire un article'))->toBe(1);
});

test('filtres : catégorie et tag ; recherche en français sans accents', function () {
    $wave = Skill::factory()->local()->create(['name' => 'Wave', 'slug' => 'wave']);
    $tuto = Article::factory()->create(['title' => 'Déploiement Laravel sur un VPS', 'body' => 'Nginx, PHP-FPM et sauvegardes.']);
    $story = Article::factory()->create(['title' => 'Notre café connecté à Abidjan', 'body' => 'Un retour de projet.', 'category' => ArticleCategory::ProjectStory]);
    $story->skills()->attach($wave);

    $titles = fn (array $filters) => Livewire::test(Index::class)->set($filters)->instance()->articles()->pluck('title')->all();

    expect($titles(['category' => 'project_story']))->toBe([$story->title])
        ->and($titles(['tag' => 'wave']))->toBe([$story->title])
        ->and($titles(['q' => 'deploiement']))->toBe([$tuto->title])
        ->and($titles(['q' => 'cafe']))->toBe([$story->title])
        ->and($titles(['category' => 'inconnue']))->toHaveCount(2);
});

test('la recherche des articles passe par l\'index GIN', function () {
    Article::factory()->count(3)->create();
    DB::statement('set enable_seqscan = off');

    $vector = (new Article)->searchVector();
    $plan = collect(DB::select("explain select id from articles where $vector @@ websearch_to_tsquery('french', f_unaccent('wave'))"))->pluck('QUERY PLAN')->join(' ');

    expect($plan)->toContain('articles_search_index');
});

test('aucun article : une invitation ; pagination par 12 sans N+1', function () {
    Livewire::test(Index::class)->set('q', 'introuvable')->assertSee('Aucun article sur ce sujet, pour l&#039;instant.', false);

    Article::factory()->count(13)->create()->each(fn (Article $a) => $a->skills()->attach(Skill::factory()->create()));
    Livewire::test(Index::class)->assertSee('13 articles')->assertSee('1 à 12 sur 13')->call('nextPage')->assertSee('13 à 13 sur 13');
});

test('la fiche : catégorie, temps de lecture, auteur, colonne de lecture, « Utile », boîte d\'action du visiteur', function () {
    $article = Article::factory()->create(['title' => 'Sécuriser une API de paiement']);

    $this->get(route('articles.show', $article))
        ->assertOk()
        ->assertSee('<title>Sécuriser une API de paiement · DevKora</title>', false)
        ->assertSee('Tutoriel')
        ->assertSee($article->reading_minutes.' min de lecture')
        ->assertSee('prose-dk prose-article', false)
        ->assertSee('Utile')
        ->assertSee('Ce que tu sais peut débloquer un autre dev.')
        ->assertSee('Rejoins la communauté pour partager tes tutoriels et tes retours d\'expérience, et gagne des cauris quand ils aident.', false);
});

test('article masqué : 404 pour les autres, visible de son auteur, absent de la liste', function () {
    $article = Article::factory()->create(['hidden_at' => now(), 'title' => 'Article masqué par la modération']);

    $this->get(route('articles.show', $article))->assertNotFound();
    $this->actingAs($article->user)->get(route('articles.show', $article))->assertOk();
    Livewire::test(Index::class)->assertDontSee('Article masqué par la modération');
});

test('écrire un article : réservé aux membres, textes de 11', function () {
    $this->get('/articles/ecrire')->assertRedirect('/connexion');

    $this->actingAs(writer())->get('/articles/ecrire')
        ->assertOk()
        ->assertSee('Publier mon article')
        ->assertSee('Chaque vote « Utile » te rapporte +5 cauris.');
});

test('l\'aperçu montre le rendu sécurisé avant publication', function () {
    Livewire::actingAs(writer())->test(Create::class)
        ->set('body', "## Intertitre\n\n<script>alert(1)</script>\n\n```php\necho 1;\n```")
        ->set('tab', 'apercu')
        ->assertSeeHtml('<h2>Intertitre</h2>')
        ->assertSeeHtml('class="code-block"')
        ->assertDontSeeHtml('<script>alert(1)</script>');
});

test('publier un article : slug, extrait, temps de lecture, tags, redirection', function () {
    $user = writer();

    Livewire::actingAs($user)->test(Create::class)
        ->set('category', 'tutorial')
        ->set('title', "Intégrer l'API Wave de bout en bout")
        ->set('body', longBody())
        ->set('tags', 'Wave, Laravel')
        ->call('publish')
        ->assertRedirect(route('articles.show', 'integrer-l-api-wave-de-bout-en-bout'));

    $article = Article::sole();
    expect($article->user_id)->toBe($user->id)
        ->and($article->category)->toBe(ArticleCategory::Tutorial)
        ->and($article->reading_minutes)->toBe(1)
        ->and($article->excerpt)->toStartWith('Contexte Une phrase utile')
        ->and($article->body_html)->toContain('class="code-block"')
        ->and($article->skills->pluck('name')->all())->toBe(['Wave', 'Laravel']);
});

test('publier un article : refus avec des messages qui disent quoi faire', function (array $data, string $field, string $message) {
    Livewire::actingAs(writer())->test(Create::class)
        ->set(array_merge(['category' => 'tutorial', 'title' => 'Un titre suffisamment long', 'body' => longBody(), 'tags' => 'Laravel'], $data))
        ->call('publish')
        ->assertHasErrors([$field])
        ->assertSee($message);

    expect(Article::count())->toBe(0);
})->with([
    [['category' => ''], 'category', 'Choisis le type d\'article.'],
    [['category' => 'poeme'], 'category', 'Choisis le type d\'article.'],
    [['title' => 'Court'], 'title', 'Ton titre est un peu court : dis ce que le lecteur va apprendre.'],
    [['body' => 'Trop court.'], 'body', 'Ton article est un peu court : 300 caractères au moins, avec un exemple concret.'],
    [['tags' => ' , '], 'tags', 'Ajoute au moins un tag, par exemple Laravel.'],
    [['tags' => 'a, b, c, d, e, f'], 'tags', 'Garde 5 tags au plus : les plus précis.'],
]);

test('5 articles par heure au plus', function () {
    $user = writer();
    foreach (range(1, 5) as $i) {
        Livewire::actingAs($user)->test(Create::class)
            ->set(['category' => 'tutorial', 'title' => "Un titre suffisamment long numéro $i", 'body' => longBody(), 'tags' => 'Laravel'])
            ->call('publish');
    }

    Livewire::actingAs($user)->test(Create::class)
        ->set(['category' => 'tutorial', 'title' => 'Un sixième titre assez long', 'body' => longBody(), 'tags' => 'Laravel'])
        ->call('publish')
        ->assertSee('Tu as publié plusieurs articles en peu de temps : réessaie dans une heure.');

    expect(Article::count())->toBe(5);
});

test('slugs : uniques, et jamais « ecrire »', function () {
    $service = app(ArticleService::class);
    $user = writer();

    $first = $service->publish($user, ArticleCategory::Tutorial, 'Bien démarrer avec Flutter', longBody(), ['Flutter']);
    $second = $service->publish($user, ArticleCategory::Tutorial, 'Bien démarrer avec Flutter', longBody(), ['Flutter']);
    $reserved = $service->publish($user, ArticleCategory::Tutorial, 'Écrire', longBody(), ['Flutter']);

    expect($first->slug)->toBe('bien-demarrer-avec-flutter')
        ->and($second->slug)->toBe('bien-demarrer-avec-flutter-2')
        ->and($reserved->slug)->toBe('ecrire-article')
        ->and(ArticleService::readingMinutes(str_repeat('mot ', 401)))->toBe(3);
});
