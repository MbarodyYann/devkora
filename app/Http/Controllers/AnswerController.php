<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnswerRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Services\AnswerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AnswerController extends Controller
{
    public function store(StoreAnswerRequest $request, Question $question, AnswerService $answers): RedirectResponse
    {
        $answer = $answers->publish($request->user(), $question, $request->validated('body'));

        return redirect()
            ->to(route('questions.show', $question).'#reponse-'.$answer->id)
            ->with('status', 'Réponse publiée. Chaque vote positif te rapporte +10 cauris.');
    }

    public function accept(Question $question, Answer $answer, AnswerService $answers): RedirectResponse
    {
        Gate::authorize('accept', $question);
        abort_unless($answer->question_id === $question->id && $answer->hidden_at === null, 404);

        $wasAccepted = $question->accepted_answer_id === $answer->id;
        $answers->accept($question, $answer);

        return redirect()
            ->to(route('questions.show', $question).'#reponse-'.$answer->id)
            ->with('status', $wasAccepted
                ? 'Réponse retirée. Ta question est de nouveau ouverte.'
                : 'Réponse acceptée : elle s\'affiche en premier pour tous ceux qui chercheront la même chose.');
    }
}
