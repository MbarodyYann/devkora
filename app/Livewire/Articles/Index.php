<?php

namespace App\Livewire\Articles;

use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\Skill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Articles (/articles) : par catégorie et par tag, recherche plein texte en français sans accents.
 */
#[Title('Articles')]
#[Layout('components.layouts.app', ['pageHero' => [
    'start' => 'Ce que tu as appris',
    'end' => 'peut débloquer un autre dev.',
    'subtitle' => 'Tutoriels, retours d\'expérience et bonnes pratiques écrits par des devs du continent, pour ton contexte.',
    'action' => ['label' => 'Écrire un article', 'route' => 'articles.create'],
]])]
class Index extends Component
{
    use WithPagination;

    public const PER_PAGE = 12;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'categorie', except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $tag = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'category', 'tag');
        $this->resetPage();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return collect([$this->q, $this->category, $this->tag])->filter()->isNotEmpty();
    }

    /**
     * @return Collection<int, Skill>
     */
    #[Computed]
    public function tagOptions(): Collection
    {
        return Skill::query()
            ->whereHas('articles', fn (Builder $q) => $q->whereNull('hidden_at'))
            ->orderByDesc('is_local')->orderBy('name')
            ->get(['name', 'slug']);
    }

    #[Computed]
    public function articles(): LengthAwarePaginator
    {
        $search = Str::squish($this->q);

        return Article::query()
            ->visible()
            ->when(ArticleCategory::tryFrom($this->category), fn (Builder $q, ArticleCategory $c) => $q->where('category', $c))
            ->when($this->tag !== '', fn (Builder $q) => $q->whereHas('skills', fn (Builder $s) => $s->where('slug', $this->tag)))
            ->when($search !== '', fn (Builder $q) => $q->fullTextSearch($search))
            ->with(['user.profile', 'skills'])
            ->latest()
            ->paginate(self::PER_PAGE);
    }

    public function paginationView(): string
    {
        return 'components.pagination';
    }

    public function render(): View
    {
        return view('livewire.articles.index', [
            'categories' => collect(ArticleCategory::cases())->mapWithKeys(fn (ArticleCategory $c) => [$c->value => $c->plural()])->all(),
        ]);
    }
}
