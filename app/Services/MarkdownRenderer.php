<?php

namespace App\Services;

use Highlight\Highlighter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Throwable;

/**
 * Markdown des membres (questions, réponses) : HTML brut échappé, liens dangereux refusés, liens externes
 * en nofollow noopener noreferrer, code coloré côté serveur (zéro JS) avec un bouton « Copier ».
 */
class MarkdownRenderer implements NodeRendererInterface
{
    /** Langages colorés (les autres blocs restent en texte brut). Alias => langage de highlight.php. */
    public const LANGUAGES = [
        'php' => 'php', 'blade' => 'php',
        'js' => 'javascript', 'javascript' => 'javascript', 'jsx' => 'javascript',
        'ts' => 'typescript', 'typescript' => 'typescript',
        'python' => 'python', 'py' => 'python',
        'dart' => 'dart', 'java' => 'java', 'kotlin' => 'kotlin', 'kt' => 'kotlin', 'go' => 'go',
        'bash' => 'bash', 'sh' => 'bash', 'shell' => 'shell', 'console' => 'shell',
        'sql' => 'sql', 'json' => 'json', 'yaml' => 'yaml', 'yml' => 'yaml',
        'html' => 'xml', 'xml' => 'xml', 'css' => 'css',
        'nginx' => 'nginx', 'dockerfile' => 'dockerfile', 'docker' => 'dockerfile', 'ini' => 'ini', 'env' => 'ini',
        'diff' => 'diff',
    ];

    private MarkdownConverter $converter;

    private Highlighter $highlighter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => [(string) parse_url((string) config('app.url'), PHP_URL_HOST)],
                'open_in_new_window' => true,
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addRenderer(FencedCode::class, $this, 10);
        $environment->addRenderer(IndentedCode::class, $this, 10);

        $this->converter = new MarkdownConverter($environment);
        $this->highlighter = new Highlighter;
    }

    public function toHtml(string $markdown): string
    {
        return trim((string) $this->converter->convert($markdown));
    }

    /**
     * Bloc de code : barre (langage, bouton « Copier ») puis le code coloré, qui défile dans son bloc.
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        $code = rtrim($node->getLiteral(), "\n");
        $info = $node instanceof FencedCode ? strtolower(trim((string) ($node->getInfoWords()[0] ?? ''))) : '';
        $language = self::LANGUAGES[$info] ?? null;

        $html = e($code);
        if ($language) {
            try {
                $html = $this->highlighter->highlight($language, $code)->value;
            } catch (Throwable) {
                $language = null;
            }
        }

        $label = $language ? e($info) : 'code';

        return '<div class="code-block" x-data="{ copied: false }">'
            .'<div class="code-toolbar"><span class="code-lang">'.$label.'</span>'
            .'<button type="button" class="code-copy" x-on:click="navigator.clipboard.writeText($root.querySelector(\'code\').innerText); copied = true; setTimeout(() => copied = false, 2000)">'
            .'<span x-text="copied ? \'Copié\' : \'Copier\'">Copier</span></button></div>'
            .'<pre><code class="hljs'.($language ? ' language-'.e($language) : '').'">'.$html.'</code></pre>'
            .'</div>';
    }
}
