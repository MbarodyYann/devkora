<?php

namespace App\Livewire;

use App\Models\Answer;
use App\Models\Question;
use App\Services\VoteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Vote +1 / −1 sur une question ou une réponse. Revoter dans le même sens annule le vote.
 */
class Vote extends Component
{
    #[Locked]
    public Question|Answer $votable;

    #[Locked]
    public int $score = 0;

    #[Locked]
    public ?int $myVote = null;

    public ?string $message = null;

    public function mount(Question|Answer $votable): void
    {
        $this->votable = $votable;
        $this->score = (int) $votable->score;
        $this->myVote = $votable->my_vote !== null ? (int) $votable->my_vote : null;
    }

    public function up(VoteService $votes): void
    {
        $this->cast($votes, 1);
    }

    public function down(VoteService $votes): void
    {
        $this->cast($votes, -1);
    }

    private function cast(VoteService $votes, int $value): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirectRoute('login');

            return;
        }

        $response = Gate::inspect('vote', [$this->votable, $value]);
        if ($response->denied()) {
            $this->message = $response->message();

            return;
        }

        if (! RateLimiter::attempt('votes:'.$user->id, 30, fn () => true)) {
            $this->message = 'Doucement : réessaie dans une minute.';

            return;
        }

        $this->myVote = $votes->vote($user, $this->votable, $value);
        $this->score = $this->votable->fresh()->score;
        $this->message = null;
    }

    public function render(): View
    {
        return view('livewire.vote', [
            'label' => $this->votable instanceof Answer ? 'cette réponse' : 'cette question',
        ]);
    }
}
