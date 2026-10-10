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
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Questions (/questions) : récentes, sans réponse ou résolues, par tag, recherche plein texte en français.
 */
#[Title('Questions')]
#[Layout('components.layouts.app', ['pageHero' => [
    'start' => 'Ta question ne se perdra plus',
    'end' => 'dans un groupe WhatsApp.',
    'subtitle' => 'Pose-la en français avec les bons tags : la communauté répond et vote, la meilleure réponse reste trouvable par tous.',
    'action' => ['label' => 'Poser une question', 'route' => 'questions.create'],
]])]
class Index extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    public const STATUSES = ['' => 'Récentes', 'sans-reponse' => 'Sans réponse', 'resolues' => 'Résolues'];

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $tag = '';

    public function updated(): void
    {
        $this->resetPage();
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
            ->latest()
            ->paginate(self::PER_PAGE);
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
