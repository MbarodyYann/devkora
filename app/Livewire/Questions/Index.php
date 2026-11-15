<?php

namespace App\Livewire\Questions;

use App\Models\Question;
use App\Models\Skill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Fil des questions (accueil) : en ce moment, récentes, sans réponse ou résolues, par tag,
 * recherche plein texte en français. Chaque onglet et chaque filtre a son adresse.
 */
class Index extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    /** Onglets (paramètre « statut » de l'adresse) ; le premier est celui par défaut. */
    public const TABS = ['' => 'En ce moment', 'recentes' => 'Récentes', 'sans-reponse' => 'Sans réponse', 'resolues' => 'Résolues'];

    /** « En ce moment » : questions des 30 derniers jours. */
    public const TRENDING_DAYS = 30;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $tag = '';

    public function mount(): void
    {
        // Un onglet inconnu dans l'adresse ramène à « En ce moment ».
        if (! array_key_exists($this->status, self::TABS)) {
            $this->status = '';
        }
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function showTab(string $tab): void
    {
        $this->status = array_key_exists($tab, self::TABS) ? $tab : '';
        $this->resetPage();
    }

    /** Adresse d'un onglet, avec la recherche et le tag en cours. */
    public function tabUrl(string $tab): string
    {
        return route('home', array_filter(['statut' => $tab, 'q' => Str::squish($this->q), 'tag' => $this->tag], fn (string $value) => $value !== ''));
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'status', 'tag');
        $this->resetPage();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return collect([$this->q, $this->status, $this->tag])->filter()->isNotEmpty();
    }

    /**
     * Tags utilisés par au moins une question visible, les locaux d'abord.
     *
     * @return Collection<int, Skill>
     */
    #[Computed]
    public function tagOptions(): Collection
    {
        return Skill::query()
            ->whereHas('questions', fn (Builder $q) => $q->whereNull('hidden_at'))
            ->orderByDesc('is_local')->orderBy('name')
            ->get(['name', 'slug', 'is_local']);
    }

    #[Computed]
    public function questions(): LengthAwarePaginator
    {
        $search = Str::squish($this->q);

        return Question::query()
            ->visible()
            ->when($this->status === 'sans-reponse', fn (Builder $q) => $q->where('answers_count', 0))
            ->when($this->status === 'resolues', fn (Builder $q) => $q->whereNotNull('accepted_answer_id'))
            ->when($this->tag !== '', fn (Builder $q) => $q->whereHas('skills', fn (Builder $s) => $s->where('slug', $this->tag)))
            ->when($search !== '', fn (Builder $q) => $q->search($search))
            ->with(['user.profile', 'skills'])
            // « En ce moment » : classement façon Hacker News (votes et réponses pondérés par l'âge).
            ->when($this->status === '', fn (Builder $q) => $q->trending($this->trendingDays($search)), fn (Builder $q) => $q->latest())
            ->paginate(self::PER_PAGE);
    }

    /**
     * Fenêtre de « En ce moment » : 30 jours. Une recherche ou un tag porte sur toutes les questions, et
     * si rien n'a été posé depuis 30 jours, le fil montre tout plutôt qu'une page vide.
     */
    private function trendingDays(string $search): int
    {
        $recent = fn () => Question::query()->visible()->where('created_at', '>=', now()->subDays(self::TRENDING_DAYS))->exists();

        return $search === '' && $this->tag === '' && $recent() ? self::TRENDING_DAYS : 36500;
    }

    public function paginationView(): string
    {
        return 'components.pagination';
    }

    public function render(): View
    {
        return view('livewire.questions.index');
    }
}
