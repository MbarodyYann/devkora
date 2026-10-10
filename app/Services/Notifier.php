<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Listing;
use App\Models\Proposal;
use App\Models\Question;
use App\Models\Review;
use App\Models\User;
use App\Notifications\Activity;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Notifications de la cloche (02, P1) : qui est prévenu de quoi, en tutoiement.
 * On ne se notifie jamais soi-même.
 */
class Notifier
{
    public function answerPosted(Answer $answer, Question $question): void
    {
        $this->send($question->user_id, $answer->user_id, 'answer_posted',
            $this->name($answer->user_id).' a répondu à ta question « '.$this->short($question->title).' ».',
            route('questions.show', $question).'#reponse-'.$answer->id);
    }

    public function answerAccepted(Answer $answer, Question $question): void
    {
        $this->send($answer->user_id, $question->user_id, 'answer_accepted',
            'Ta réponse à « '.$this->short($question->title).' » a été acceptée : +15 cauris.',
            route('questions.show', $question).'#reponse-'.$answer->id);
    }

    /**
     * Votes « Utile » regroupés : une seule notification non lue par article, mise à jour à chaque vote
     * (« 3 devs ont trouvé ton article utile »).
     */
    public function articleUseful(Article $article, User $voter): void
    {
        if ($voter->id === $article->user_id) {
            return;
        }

        $author = User::findOrFail($article->user_id);
        /** @var DatabaseNotification|null $existing */
        $existing = $author->unreadNotifications()
            ->where('data->kind', 'article_useful')
            ->where('data->article_id', $article->id)
            ->first();

        $count = ($existing?->data['count'] ?? 0) + 1;
        $message = ($count === 1 ? $voter->firstName().' a trouvé' : "$count devs ont trouvé").' ton article « '.$this->short($article->title).' » utile.';
        $data = ['kind' => 'article_useful', 'message' => $message, 'url' => route('articles.show', $article), 'article_id' => $article->id, 'count' => $count];

        if ($existing) {
            $existing->forceFill(['data' => $data, 'created_at' => now()])->save();
        } else {
            $author->notify(new Activity('article_useful', $message, $data['url'], ['article_id' => $article->id, 'count' => 1]));
        }
    }

    public function proposalReceived(Proposal $proposal, Listing $listing): void
    {
        $who = $this->name($proposal->user_id);
        $this->send($listing->user_id, $proposal->user_id, 'proposal_received',
            $listing->isMission()
                ? "$who propose son aide pour « ".$this->short($listing->title).' ».'
                : "$who veut rejoindre ton équipe « ".$this->short($listing->title).' ».',
            route('listings.show', $listing).($listing->isMission() ? '#propositions' : '#demandes'));
    }

    public function teamRequestDecided(Proposal $proposal, Listing $listing): void
    {
        if ($proposal->status === ProposalStatus::Accepted) {
            $this->send($proposal->user_id, $listing->user_id, 'team_accepted',
                'Bienvenue dans l\'équipe « '.$this->short($listing->title).' » !',
                route('listings.show', $listing).'#equipe');
        }
    }

    /** Fonds sécurisés : le dev retenu peut commencer, les autres sont prévenus (11). */
    public function devRetained(Listing $listing, Proposal $retained): void
    {
        $this->send($retained->user_id, $listing->user_id, 'proposal_retained',
            'Tu es retenu pour « '.$this->short($listing->title).' » : les fonds sont sécurisés, tu peux commencer.',
            route('listings.show', $listing));

        $listing->proposals()->where('status', ProposalStatus::Declined)->whereKeyNot($retained->id)->pluck('user_id')
            ->each(fn (int $userId) => $this->send($userId, $listing->user_id, 'proposal_declined',
                'L\'auteur de « '.$this->short($listing->title).' » a retenu un autre dev. D\'autres missions t\'attendent.',
                route('listings.index', ['type' => 'mission'])));
    }

    public function missionCompleted(Listing $listing, User $dev): void
    {
        $this->send($dev->id, $listing->user_id, 'mission_completed',
            'Mission « '.$this->short($listing->title).' » terminée : le paiement est libéré et tu reçois +20 cauris.',
            route('listings.show', $listing));
    }

    public function reviewReceived(Review $review, Listing $listing): void
    {
        $this->send($review->reviewee_id, $review->reviewer_id, 'review_received',
            $this->name($review->reviewer_id).' t\'a laissé un avis '.$review->rating.' étoile'.($review->rating > 1 ? 's' : '').' pour « '.$this->short($listing->title).' ».',
            route('devs.show', $review->reviewee_id).'#missions');
    }

    private function send(int $recipientId, int $actorId, string $kind, string $message, string $url): void
    {
        if ($recipientId === $actorId) {
            return;
        }

        User::findOrFail($recipientId)->notify(new Activity($kind, $message, $url));
    }

    private function name(int $userId): string
    {
        return User::query()->whereKey($userId)->value('name') ?? 'Un membre';
    }

    private function short(string $title): string
    {
        return Str::limit($title, 60);
    }
}
