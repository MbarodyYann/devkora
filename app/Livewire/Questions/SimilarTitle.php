<?php

namespace App\Livewire\Questions;

use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Champ titre de « Poser une question » : pendant la saisie, les 3 questions les plus proches
 * (plein texte, sans accents). Indicatif : la publication n'est jamais bloquée.
 */
class SimilarTitle extends Component
{
    public const MIN_LENGTH = 4;

    public string $title = '';

    public function mount(?string $value = null): void
    {
        $this->title = (string) $value;
    }

    /**
     * @return Collection<int, Question>
     */
    #[Computed]
    public function similar(): Collection
    {
        $terms = Str::squish($this->title);
        if (mb_strlen($terms) < self::MIN_LENGTH) {
            return collect();
        }

        // Recherche « ou » sur les mots significatifs du titre, pour trouver des questions proches et pas identiques.
        $words = collect(preg_split('/[^\p{L}\p{N}]+/u', $terms))->filter(fn ($w) => mb_strlen($w) >= 3)->take(8);
        if ($words->isEmpty()) {
            return collect();
        }

        return Question::query()->visible()
            ->fullTextSearch($words->join(' or '))
            ->limit(3)
            ->get(['id', 'title', 'answers_count', 'accepted_answer_id']);
    }

    public function render(): View
    {
        return view('livewire.questions.similar-title');
    }
}
