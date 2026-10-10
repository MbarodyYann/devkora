<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Skill;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Modifier les tags de la question d'un autre membre : à partir de 500 cauris, journalisé (retag).
 */
class QuestionTagController extends Controller
{
    public function update(Request $request, Question $question, ModerationService $moderation): RedirectResponse
    {
        Gate::authorize('retag', $question);
        $request->validate(['tags' => ['required', 'string', 'max:300']], ['tags.required' => 'Ajoute au moins un tag.']);

        $tags = Skill::parseList($request->input('tags'));
        if ($tags === [] || count($tags) > 5) {
            throw ValidationException::withMessages(['tags' => 'Garde de 1 à 5 tags.']);
        }

        $moderation->retag($request->user(), $question, $tags);

        return back()->with('status', 'Tags mis à jour. La modification est dans le journal de modération.');
    }
}
