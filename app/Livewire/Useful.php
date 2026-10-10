<?php

namespace App\Livewire;

use App\Models\Article;
use App\Services\VoteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Vote « Utile » sur un article : +1 seulement, annulable, +5 cauris pour l'auteur.
 */
class Useful extends Component
{
    #[Locked]
    public Article $article;

    #[Locked]
    public int $score = 0;

    #[Locked]
    public bool $voted = false;

    public ?string $message = null;

    public function mount(Article $article): void
    {
        $this->article = $article;
        $this->score = (int) $article->score;
        $this->voted = (int) $article->my_vote === 1;
    }

    public function toggle(VoteService $votes): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirectRoute('login');

            return;
        }

        $response = Gate::inspect('vote', [$this->article, 1]);
        if ($response->denied()) {
            $this->message = $response->message();

            return;
        }

        if (! RateLimiter::attempt('votes:'.$user->id, 30, fn () => true)) {
            $this->message = 'Doucement : réessaie dans une minute.';

            return;
        }

        $this->voted = $votes->vote($user, $this->article, 1) === 1;
        $this->score = (int) $this->article->fresh()->score;
        $this->message = null;
    }

    public function render(): View
    {
        return view('livewire.useful');
    }
}
