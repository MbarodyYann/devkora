<?php

use App\Services\MarkdownRenderer;
use App\Support\Text;

function md(string $markdown): string
{
    return app(MarkdownRenderer::class)->toHtml($markdown);
}

test('le HTML brut est échappé (XSS)', function (string $attack) {
    $html = md($attack);

    expect($html)->not->toContain('<script')
        ->not->toContain('<img')
        ->not->toContain('<iframe')
        ->not->toMatch('/<[a-z][^>]*\son\w+=/i');
})->with([
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '<iframe src="https://exemple.com"></iframe>',
    "Texte\n\n<div onclick=\"alert(1)\">clic</div>",
]);

test('les liens dangereux sont neutralisés', function () {
    expect(md('[clic](javascript:alert(1))'))->not->toContain('javascript:')
        ->and(md('[clic](data:text/html;base64,PHNjcmlwdD4=)'))->not->toContain('data:text/html')
        ->and(md('![img](javascript:alert(1))'))->not->toContain('javascript:');
});

test('les liens externes sont en nofollow noopener noreferrer et s\'ouvrent dans un nouvel onglet', function () {
    expect(md('[doc](https://docs.wave.com)'))->toContain('rel="nofollow noopener noreferrer"')->toContain('target="_blank"');
});

test('le code est coloré côté serveur, échappé, avec un bouton « Copier »', function () {
    $html = md("```php\n\$x = '<b>';\n```");

    expect($html)->toContain('class="code-block"')
        ->toContain('<span class="code-lang">php</span>')
        ->toContain('Copier')
        ->toContain('hljs-string')
        ->toContain('&lt;b&gt;')
        ->not->toContain("'<b>'");
});

test('au moins 14 langages colorés, les autres en texte brut', function () {
    $languages = array_unique(array_values(MarkdownRenderer::LANGUAGES));
    expect(count($languages))->toBeGreaterThanOrEqual(14);

    foreach (['php', 'js', 'ts', 'python', 'dart', 'java', 'kotlin', 'go', 'bash', 'sql', 'json', 'yaml', 'html', 'css', 'nginx', 'dockerfile'] as $lang) {
        expect(md("```$lang\nx\n```"))->toContain('class="hljs language-');
    }

    expect(md("```brainfuck\n+++<script>\n```"))->toContain('<span class="code-lang">code</span>')->toContain('&lt;script&gt;')
        ->and(md("    indenté\n"))->toContain('class="code-block"');
});

test('les extraits retirent le Markdown sans abîmer le texte', function () {
    $markdown = "# Titre\n\nJ'utilise **Laravel** et `API_URL` avec [la doc](https://x.dev) et snake_case.\n\n```php\necho 1;\n```\n\n- Fin";

    expect(Text::excerpt($markdown))->toBe("Titre J'utilise Laravel et API_URL avec la doc et snake_case. Fin")
        ->and(mb_strlen(Text::excerpt(str_repeat('mot ', 200), 50)))->toBeLessThanOrEqual(53);
});
