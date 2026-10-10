<?php

namespace App\Livewire\Articles;

use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\Skill;
use App\Services\ArticleService;
use App\Services\MarkdownRenderer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Écrire un article (/articles/ecrire), avec un aperçu rendu par le serveur (même rendu que la publication).
 */
#[Title('Écrire un article')]
#[Layout('components.layouts.app', ['noindex' => true])]
class Create extends Component
{
    public const MAX_TAGS = 5;

    public string $category = '';

    public string $title = '';

    public string $body = '';

    public string $tags = '';

    /** Onglet affiché : « ecrire » ou « apercu ». */
    public string $tab = 'ecrire';

    public function mount(): void
    {
        Gate::authorize('create', Article::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ArticleCategory::class)],
            'title' => ['required', 'string', 'min:15', 'max:150'],
            'body' => ['required', 'string', 'min:300', 'max:60000'],
            'tags' => ['required', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'category.required' => 'Choisis le type d\'article.',
            'category.enum' => 'Choisis le type d\'article.',
            'title.required' => 'Donne un titre à ton article.',
            'title.min' => 'Ton titre est un peu court : dis ce que le lecteur va apprendre.',
            'body.required' => 'Écris ton article avant de le publier.',
            'body.min' => 'Ton article est un peu court : 300 caractères au moins, avec un exemple concret.',
            'tags.required' => 'Ajoute au moins un tag, par exemple Laravel.',
        ];
    }

    #[Computed]
    public function preview(): string
    {
        return trim($this->body) === '' ? '' : app(MarkdownRenderer::class)->toHtml($this->body);
    }

    public function publish(ArticleService $articles)
    {
        Gate::authorize('create', Article::class);
        $this->validate();

        $tags = Skill::parseList($this->tags);
        if ($tags === []) {
            $this->addError('tags', 'Ajoute au moins un tag, par exemple Laravel.');

            return null;
        }
        if (count($tags) > self::MAX_TAGS) {
            $this->addError('tags', 'Garde '.self::MAX_TAGS.' tags au plus : les plus précis.');

            return null;
        }

        if (! RateLimiter::attempt('articles:'.auth()->id(), 5, fn () => true, 3600)) {
            $this->addError('body', 'Tu as publié plusieurs articles en peu de temps : réessaie dans une heure.');

            return null;
        }

        $article = $articles->publish(auth()->user(), ArticleCategory::from($this->category), trim($this->title), $this->body, $tags);

        session()->flash('status', 'Article publié. Chaque vote « Utile » te rapportera +5 cauris.');

        return $this->redirectRoute('articles.show', $article);
    }

    public function render(): View
    {
        return view('livewire.articles.create', [
            'categories' => ArticleCategory::options(),
            'suggestedTags' => Skill::query()->orderByDesc('is_local')->orderBy('name')->pluck('name'),
        ]);
    }
}
