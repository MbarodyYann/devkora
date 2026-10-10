<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Models\Question;
use App\Models\Skill;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Question::class);

        return view('questions.create', [
            // Tags proposés : les locaux d'abord (Wave, USSD…), puis les plus utilisés.
            'suggestedTags' => Skill::query()->withCount('questions')
                ->orderByDesc('is_local')->orderByDesc('questions_count')->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function store(StoreQuestionRequest $request, QuestionService $questions): RedirectResponse
    {
        $question = $questions->publish($request->user(), trim($request->validated('title')), $request->validated('body'), $request->tagNames());

        return redirect()
            ->route('questions.show', $question)
            ->with('status', 'Question publiée. Les réponses de la communauté apparaîtront juste en dessous.');
    }

    public function show(Question $question): View
    {
        // Contenu masqué : une 404, comme s'il n'existait pas (02, « Règles métier »).
        abort_unless(Gate::allows('view', $question), 404);
        $me = auth()->user();

        $question->load(['user.profile', 'skills']);
        $question->setAttribute('my_vote', $me ? $question->votes()->where('user_id', $me->id)->value('value') : null);

        // Réponse acceptée d'abord, puis par score, puis la plus ancienne.
        $answers = $question->answers()
            ->visible()
            ->select('answers.*')
            ->withMyVote($me)
            ->with('user.profile')
            ->orderByRaw('answers.id = ? desc', [(int) $question->accepted_answer_id])
            ->orderByDesc('score')
            ->oldest()
            ->get();

        return view('questions.show', [
            'question' => $question,
            'answers' => $answers,
            'answeredByMe' => $me && $answers->contains('user_id', $me->id),
        ]);
    }
}
